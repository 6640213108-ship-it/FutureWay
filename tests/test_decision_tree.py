# -*- coding: utf-8 -*-
"""
Unit tests สำหรับ decision_tree.py

รันจาก root ของ repo:
    python -m unittest discover -s tests -v

ทุกเทสรันแบบ offline: ไม่ต้องมี MySQL และไม่ต้องตั้ง MYSQLPASSWORD
เพราะฟังก์ชันหลักรับข้อมูลคำถาม/สาขาเข้ามาตรงๆ ได้ (dependency injection)
ส่วน main() ใช้ unittest.mock แทนฟังก์ชันที่ดึงข้อมูลจากฐานข้อมูล
"""

import io
import json
import os
import sys
import unittest
from unittest import mock

# ให้ import decision_tree ได้ไม่ว่าจะรันจากโฟลเดอร์ไหน
sys.path.insert(0, os.path.dirname(os.path.dirname(os.path.abspath(__file__))))

import decision_tree as dt  # noqa: E402


# ========================================
# ตัวช่วยสร้างข้อมูลจำลอง
# ========================================
GRADE_KEYS = ['math', 'sci', 'eng', 'thai', 'social', 'art']


def make_grades(value=4.0, **overrides):
    grades = {k: value for k in GRADE_KEYS}
    grades.update(overrides)
    return grades


def make_branch(id=1, name='สาขา', faculty='คณะ', mbti_match=('INTJ',),
                mins=None, weights=None, riasec=None):
    """สร้างแถวสาขาขั้นต่ำเท่าที่ calculate_score / run_decision_tree ใช้"""
    branch = {
        'id':          id,
        'name':        name,
        'faculty':     faculty,
        'description': f'รายละเอียด {name}',
        'mbti_match':  json.dumps(list(mbti_match)),
    }
    for k in GRADE_KEYS:
        branch[f'min_{k}']    = (mins or {}).get(k, 0)
        branch[f'weight_{k}'] = (weights or {}).get(k, 0)
    for L in dt.RIASEC_LETTERS:
        branch[f'riasec_{L.lower()}'] = (riasec or {}).get(L, 0)
    return branch


def make_mbti_question(id, category, a_trait=None, b_trait=None):
    return {
        'id':             id,
        'category':       category,
        'question_no':    id,
        'question_text':  f'คำถาม {id}',
        'option_a_text':  'A',
        'option_a_trait': category[0] if a_trait is None else a_trait,
        'option_b_text':  'B',
        'option_b_trait': category[1] if b_trait is None else b_trait,
    }


# 3 ข้อต่อมิติ id 1-12
MBTI_QUESTIONS = (
    [make_mbti_question(i, 'EI') for i in (1, 2, 3)] +
    [make_mbti_question(i, 'SN') for i in (4, 5, 6)] +
    [make_mbti_question(i, 'TF') for i in (7, 8, 9)] +
    [make_mbti_question(i, 'JP') for i in (10, 11, 12)]
)


def answer(qid, selected):
    return {'question_id': qid, 'selected': selected}


# RIASEC: R มี 2 ข้อ, I มี 2 ข้อ, A มี 1 ข้อ, S/E/C ไม่มีคำถามเลย
RIASEC_QUESTIONS = [
    {'id': 1, 'letter': 'R', 'question_no': 1},
    {'id': 2, 'letter': 'R', 'question_no': 2},
    {'id': 3, 'letter': 'I', 'question_no': 1},
    {'id': 4, 'letter': 'I', 'question_no': 2},
    {'id': 5, 'letter': 'a', 'question_no': 1},   # ตัวพิมพ์เล็ก ต้องถูก normalize
]


# ========================================
# resolve_mbti_from_answers
# ========================================
class ResolveMbtiTests(unittest.TestCase):

    def test_majority_wins_per_dimension(self):
        answers = [
            answer(1, 'A'), answer(2, 'A'), answer(3, 'B'),    # E 2 : I 1 -> E
            answer(4, 'A'), answer(5, 'A'), answer(6, 'A'),    # S 3 : N 0 -> S
            answer(7, 'B'), answer(8, 'B'), answer(9, 'A'),    # T 1 : F 2 -> F
            answer(10, 'A'), answer(11, 'B'), answer(12, 'A'), # J 2 : P 1 -> J
        ]
        result = dt.resolve_mbti_from_answers(answers, questions=MBTI_QUESTIONS)
        self.assertEqual(result['mbti'], 'ESFJ')
        self.assertEqual(result['matched'], 12)
        self.assertEqual(result['total'], 12)
        self.assertEqual(result['detail']['EI'], {'E': 2, 'I': 1})
        self.assertEqual(result['detail']['TF'], {'T': 1, 'F': 2})

    def test_tie_breaks_default_to_infp(self):
        # ไม่ตอบเลย -> ทุกมิติเสมอ 0:0 -> I N F P
        result = dt.resolve_mbti_from_answers([], questions=MBTI_QUESTIONS)
        self.assertEqual(result['mbti'], 'INFP')
        self.assertEqual(result['matched'], 0)

        # เสมอแบบมีคะแนนจริง 1:1 ต่อมิติ ก็ยังไปทาง I N F P
        answers = [answer(1, 'A'), answer(2, 'B'), answer(4, 'A'), answer(5, 'B'),
                   answer(7, 'A'), answer(8, 'B'), answer(10, 'A'), answer(11, 'B')]
        result = dt.resolve_mbti_from_answers(answers, questions=MBTI_QUESTIONS)
        self.assertEqual(result['mbti'], 'INFP')
        self.assertEqual(result['matched'], 8)

    def test_unknown_question_ids_are_skipped(self):
        answers = [answer(1, 'A'), answer(999, 'A'), answer('abc', 'B')]
        result = dt.resolve_mbti_from_answers(answers, questions=MBTI_QUESTIONS)
        self.assertEqual(result['matched'], 1)
        self.assertEqual(result['total'], 3)
        self.assertEqual(result['detail']['EI'], {'E': 1, 'I': 0})

    def test_string_question_ids_match_int_ids(self):
        # PHP อาจส่ง id มาเป็น string "1" ต้องจับคู่กับ id 1 (int) ได้
        answers = [answer('1', 'A'), answer(' 2 ', 'A')]
        result = dt.resolve_mbti_from_answers(answers, questions=MBTI_QUESTIONS)
        self.assertEqual(result['matched'], 2)
        self.assertEqual(result['mbti'][0], 'E')

    def test_invalid_selection_is_skipped(self):
        answers = [answer(1, 'C'), answer(2, ''), answer(3, None)]
        result = dt.resolve_mbti_from_answers(answers, questions=MBTI_QUESTIONS)
        self.assertEqual(result['matched'], 0)

    def test_empty_trait_falls_back_to_a_b_position(self):
        questions = [make_mbti_question(1, 'EI', a_trait='', b_trait=None),
                     make_mbti_question(2, 'EI', a_trait='', b_trait='')]
        result = dt.resolve_mbti_from_answers([answer(1, 'A'), answer(2, 'B')], questions=questions)
        # A -> ตัวแรกของมิติ (E), B -> ตัวที่สอง (I)
        self.assertEqual(result['detail']['EI'], {'E': 1, 'I': 1})
        self.assertEqual(result['matched'], 2)

    def test_full_word_traits_are_handled(self):
        questions = [make_mbti_question(1, 'EI', a_trait='Extrovert', b_trait='Introvert'),
                     make_mbti_question(2, 'TF', a_trait='thinking', b_trait='feeling')]
        result = dt.resolve_mbti_from_answers([answer(1, 'B'), answer(2, 'A')], questions=questions)
        self.assertEqual(result['detail']['EI'], {'E': 0, 'I': 1})
        self.assertEqual(result['detail']['TF'], {'T': 1, 'F': 0})

    def test_trait_not_in_dimension_falls_back(self):
        # DB ใส่ trait ผิดมิติ (เช่น 'T' ในข้อ EI) ต้องใช้ตำแหน่ง A/B แทน
        questions = [make_mbti_question(1, 'EI', a_trait='T', b_trait='X')]
        result = dt.resolve_mbti_from_answers([answer(1, 'A'), answer(1, 'B')], questions=questions)
        self.assertEqual(result['detail']['EI'], {'E': 1, 'I': 1})

    def test_unknown_category_is_skipped(self):
        questions = [make_mbti_question(1, 'ZZ', a_trait='Z', b_trait='Z')]
        result = dt.resolve_mbti_from_answers([answer(1, 'A')], questions=questions)
        self.assertEqual(result['matched'], 0)

    def test_fetches_from_db_when_questions_not_given(self):
        with mock.patch.object(dt, 'get_mbti_questions', return_value=MBTI_QUESTIONS) as fetch:
            result = dt.resolve_mbti_from_answers([answer(1, 'A')])
        fetch.assert_called_once_with()
        self.assertEqual(result['matched'], 1)


# ========================================
# resolve_riasec_from_selection
# ========================================
class ResolveRiasecTests(unittest.TestCase):

    def test_score_ratios_per_letter(self):
        result = dt.resolve_riasec_from_selection([1, 2, 3, 5], questions=RIASEC_QUESTIONS)
        self.assertEqual(result['scores']['R'], 1.0)   # 2/2
        self.assertEqual(result['scores']['I'], 0.5)   # 1/2
        self.assertEqual(result['scores']['A'], 1.0)   # 1/1 (ตัวพิมพ์เล็กใน DB)
        self.assertEqual(result['matched'], 4)
        self.assertEqual(result['total_questions'], 5)

    def test_letters_without_questions_score_zero(self):
        result = dt.resolve_riasec_from_selection([1], questions=RIASEC_QUESTIONS)
        for L in ('S', 'E', 'C'):
            self.assertEqual(result['scores'][L], 0.0)
        self.assertEqual(set(result['scores'].keys()), set(dt.RIASEC_LETTERS))

    def test_unknown_ids_and_string_ids(self):
        result = dt.resolve_riasec_from_selection(['1', ' 3 ', 999, 'x'], questions=RIASEC_QUESTIONS)
        self.assertEqual(result['matched'], 2)
        self.assertEqual(result['scores']['R'], 0.5)
        self.assertEqual(result['scores']['I'], 0.5)

    def test_nothing_selected(self):
        result = dt.resolve_riasec_from_selection([], questions=RIASEC_QUESTIONS)
        self.assertEqual(result['matched'], 0)
        self.assertTrue(all(v == 0.0 for v in result['scores'].values()))

    def test_question_with_unknown_letter_is_ignored(self):
        questions = RIASEC_QUESTIONS + [{'id': 6, 'letter': 'Q', 'question_no': 1}]
        result = dt.resolve_riasec_from_selection([6], questions=questions)
        self.assertEqual(result['matched'], 0)
        self.assertEqual(result['total_questions'], 6)

    def test_fetches_from_db_when_questions_not_given(self):
        with mock.patch.object(dt, 'get_riasec_questions', return_value=RIASEC_QUESTIONS) as fetch:
            result = dt.resolve_riasec_from_selection([1])
        fetch.assert_called_once_with()
        self.assertEqual(result['matched'], 1)


# ========================================
# calculate_score
# ========================================
class CalculateScoreTests(unittest.TestCase):

    def test_mbti_position_scores_in_grade_mode(self):
        # น้ำหนักเกรดเป็น 0 ทั้งหมด -> คะแนนมาจาก MBTI ล้วนๆ
        branch = make_branch(mbti_match=('INTJ', 'ENTJ', 'INTP'))
        self.assertEqual(dt.calculate_score(branch, make_grades(), 'INTJ'), 60.0)
        self.assertEqual(dt.calculate_score(branch, make_grades(), 'ENTJ'), 56.0)
        self.assertEqual(dt.calculate_score(branch, make_grades(), 'INTP'), 52.0)

    def test_mbti_position_beyond_table_uses_last_score(self):
        branch = make_branch(mbti_match=('AAAA', 'BBBB', 'CCCC', 'DDDD', 'EEEE', 'INTJ', 'ENTJ'))
        self.assertEqual(dt.calculate_score(branch, make_grades(), 'INTJ'), 44.0)
        self.assertEqual(dt.calculate_score(branch, make_grades(), 'ENTJ'), 44.0)

    def test_mbti_match_accepts_already_parsed_list(self):
        branch = make_branch()
        branch['mbti_match'] = ['INTJ']
        self.assertEqual(dt.calculate_score(branch, make_grades(), 'INTJ'), 60.0)

    def test_partial_match_path(self):
        branch = make_branch(mbti_match=('INTJ', 'ESFP'))
        # ISTJ ตรงกับ INTJ 3/4 -> 3/4 * 40 = 30
        self.assertEqual(dt.calculate_score(branch, make_grades(), 'ISTJ'), 30.0)
        # ENFP ตรงกับ ESFP 3/4 (ใช้ค่าที่ตรงมากที่สุดในลิสต์)
        self.assertEqual(dt.calculate_score(branch, make_grades(), 'ENFP'), 30.0)
        # ตรงมากสุด 2/4 -> 2/4 * 40 = 20
        self.assertEqual(dt.calculate_score(branch, make_grades(), 'ISFJ'), 20.0)   # vs INTJ = I,J / vs ESFP = S,F
        self.assertEqual(dt.calculate_score(branch, make_grades(), 'ESTJ'), 20.0)   # vs INTJ = T,J / vs ESFP = E,S
        # ไม่ตรงสักตัวกับสาขาที่มีลิสต์ตัวเดียว -> 0
        self.assertEqual(dt.calculate_score(make_branch(mbti_match=('INTJ',)), make_grades(), 'ESFP'), 0.0)

    def test_partial_match_in_interest_mode(self):
        branch = make_branch(mbti_match=('INTJ',))
        # 3/4 * 34 = 25.5
        self.assertEqual(dt.calculate_score(branch, None, 'ISTJ', riasec={}), 25.5)

    def test_below_min_penalty(self):
        branch = make_branch(mbti_match=('INTJ',), mins={'math': 3.0, 'sci': 3.0})
        grades = make_grades(4.0, math=2.5)          # ต่ำกว่าขั้นต่ำ 1 วิชา
        self.assertEqual(dt.calculate_score(branch, grades, 'INTJ'), 52.0)
        grades = make_grades(4.0, math=2.5, sci=1.0)  # ต่ำกว่าขั้นต่ำ 2 วิชา
        self.assertEqual(dt.calculate_score(branch, grades, 'INTJ'), 44.0)
        # เกรดเท่าขั้นต่ำพอดี ไม่โดนหัก
        grades = make_grades(4.0, math=3.0, sci=3.0)
        self.assertEqual(dt.calculate_score(branch, grades, 'INTJ'), 60.0)

    def test_min_zero_means_no_requirement(self):
        branch = make_branch(mbti_match=('INTJ',))
        self.assertEqual(dt.calculate_score(branch, make_grades(0.0), 'INTJ'), 60.0)

    def test_weighted_grade_score(self):
        branch = make_branch(mbti_match=('INTJ',), weights={'math': 1, 'sci': 1})
        # เกรดเต็มทุกวิชา -> ratio 1 -> +40 -> 100
        self.assertEqual(dt.calculate_score(branch, make_grades(4.0), 'INTJ'), 100.0)
        # math 4, sci 2 -> weighted 1.5 / 2 = 0.75 -> 0.75^1.5 * 40
        grades = make_grades(4.0, sci=2.0)
        expected = round(60 + (0.75 ** 1.5) * 40, 2)
        self.assertEqual(dt.calculate_score(branch, grades, 'INTJ'), expected)
        self.assertAlmostEqual(expected, 85.98, places=2)

    def test_interest_mode_uses_riasec_weights(self):
        branch = make_branch(mbti_match=('INTJ',), riasec={'R': 2, 'I': 2})
        riasec = {'R': 1.0, 'I': 0.5, 'A': 0.0, 'S': 0.0, 'E': 0.0, 'C': 0.0}
        # weighted 2*1 + 2*0.5 = 3 / total 4 = 0.75 -> 0.75^1.3 * 50 + 50
        expected = round(50 + (0.75 ** 1.3) * 50, 2)
        self.assertEqual(dt.calculate_score(branch, None, 'INTJ', riasec=riasec), expected)
        # โปรไฟล์เต็มทุกมิติ -> 100
        full = {L: 1.0 for L in dt.RIASEC_LETTERS}
        self.assertEqual(dt.calculate_score(branch, None, 'INTJ', riasec=full), 100.0)

    def test_interest_mode_ignores_grade_fields_and_min_penalty(self):
        branch = make_branch(mbti_match=('INTJ',), mins={'math': 4.0}, weights={'math': 1})
        # ไม่มีเกรด -> ไม่มี penalty และไม่มีคะแนนเกรด; ไม่มีน้ำหนัก riasec -> คะแนน MBTI ล้วน 50
        self.assertEqual(dt.calculate_score(branch, None, 'INTJ', riasec={'R': 1.0}), 50.0)

    def test_riasec_missing_letters_default_to_zero(self):
        branch = make_branch(mbti_match=('INTJ',), riasec={'R': 1, 'C': 1})
        self.assertEqual(dt.calculate_score(branch, None, 'INTJ', riasec={'R': 1.0}),
                         round(50 + (0.5 ** 1.3) * 50, 2))

    def test_result_clamped_to_0_100(self):
        # ไม่ตรง MBTI สักตัว (0 คะแนน) และต่ำกว่าขั้นต่ำ 6 วิชา -> ติดลบ -> 0
        branch = make_branch(mbti_match=('INTJ',), mins={k: 4.0 for k in GRADE_KEYS})
        self.assertEqual(dt.calculate_score(branch, make_grades(1.0), 'ESFP'), 0.0)
        # เพดาน 100
        branch = make_branch(mbti_match=('INTJ',), weights={k: 1 for k in GRADE_KEYS})
        self.assertEqual(dt.calculate_score(branch, make_grades(4.0), 'INTJ'), 100.0)

    def test_string_values_from_db_are_accepted(self):
        # mysql อาจคืน DECIMAL เป็น str/Decimal -> ต้องแปลงเป็น float ได้
        branch = make_branch(mbti_match=('INTJ',), weights={'math': '1.00'}, mins={'sci': '3.00'})
        grades = {k: '4.00' for k in GRADE_KEYS}
        self.assertEqual(dt.calculate_score(branch, grades, 'INTJ'), 100.0)


# ========================================
# run_decision_tree
# ========================================
def sample_branches():
    """
    5 คณะ; คณะวิศวะมี 6 สาขา (ทดสอบตัด 5) และมีคะแนนเรียงกัน
    ทุกสาขาไม่มีน้ำหนักเกรด -> คะแนนขึ้นกับตำแหน่ง MBTI เท่านั้น
    """
    branches = []
    positions = [('INTJ',), ('ENTJ', 'INTJ'), ('A', 'B', 'INTJ'), ('A', 'B', 'C', 'INTJ'),
                 ('A', 'B', 'C', 'D', 'INTJ'), ('ESFP',)]
    for i, m in enumerate(positions):
        branches.append(make_branch(id=100 + i, name=f'วิศวะ {i}', faculty='วิศวกรรมศาสตร์', mbti_match=m))
    branches.append(make_branch(id=200, name='ศิลป์ 0', faculty='ศิลปกรรมศาสตร์', mbti_match=('ENTJ', 'INTJ')))
    branches.append(make_branch(id=300, name='บริหาร 0', faculty='บริหารธุรกิจ', mbti_match=('A', 'B', 'INTJ')))
    branches.append(make_branch(id=400, name='ครุ 0', faculty='ครุศาสตร์', mbti_match=('A', 'B', 'C', 'INTJ')))
    branches.append(make_branch(id=500, name='ไม่มีคณะ', faculty=None, mbti_match=('ESFP',)))
    return branches


class RunDecisionTreeTests(unittest.TestCase):

    def test_top3_faculties_ordered_by_best_score(self):
        result = dt.run_decision_tree(make_grades(3.0), 'INTJ', branches=sample_branches())
        self.assertEqual(result['mbti'], 'INTJ')
        self.assertEqual(result['branches_considered'], 10)
        cats = result['top_categories']
        self.assertEqual(len(cats), 3)
        self.assertEqual([c['faculty'] for c in cats], ['วิศวกรรมศาสตร์', 'ศิลปกรรมศาสตร์', 'บริหารธุรกิจ'])
        self.assertEqual([c['best_score'] for c in cats], [60.0, 56.0, 52.0])
        best_scores = [c['best_score'] for c in cats]
        self.assertEqual(best_scores, sorted(best_scores, reverse=True))

    def test_at_most_5_branches_per_faculty_sorted_by_score(self):
        result = dt.run_decision_tree(make_grades(3.0), 'INTJ', branches=sample_branches())
        eng = result['top_categories'][0]
        self.assertEqual(len(eng['branches']), 5)
        scores = [b['score'] for b in eng['branches']]
        self.assertEqual(scores, [60.0, 56.0, 52.0, 48.0, 44.0])
        self.assertEqual(eng['best_score'], eng['branches'][0]['score'])
        for b in eng['branches']:
            self.assertEqual(set(b.keys()), {'id', 'name', 'faculty', 'description', 'score'})

    def test_avg_grade_rounding(self):
        grades = make_grades(3.0, math=3.33, sci=3.67, eng=2.5)   # (3.33+3.67+2.5+3+3+3)/6 = 3.0833..
        result = dt.run_decision_tree(grades, 'INFP', branches=sample_branches())
        self.assertEqual(result['avg_grade'], 3.08)

    def test_science_boost_applied_for_thinker_with_high_grades(self):
        result = dt.run_decision_tree(make_grades(3.5), 'INTJ', branches=sample_branches())
        eng = result['top_categories'][0]
        self.assertEqual(eng['faculty'], 'วิศวกรรมศาสตร์')
        self.assertEqual(eng['best_score'], 65.0)
        for b in eng['branches']:
            self.assertEqual(b['note'], dt.SCIENCE_BOOST_NOTE)
            self.assertIn('⭐', b['note'])
        # คณะที่ไม่ใช่สายวิทย์ไม่ได้ boost และไม่มี note
        arts = result['top_categories'][1]
        self.assertEqual(arts['faculty'], 'ศิลปกรรมศาสตร์')
        self.assertEqual(arts['best_score'], 56.0)
        self.assertNotIn('note', arts['branches'][0])

    def test_science_boost_capped_at_100(self):
        branches = [make_branch(id=1, faculty='วิทยาศาสตร์', mbti_match=('INTJ',),
                                weights={'math': 1})]
        result = dt.run_decision_tree(make_grades(4.0), 'INTJ', branches=branches)
        self.assertEqual(result['top_categories'][0]['best_score'], 100.0)

    def test_science_boost_not_applied_when_conditions_fail(self):
        # เกรดเฉลี่ยต่ำกว่า 3.5
        result = dt.run_decision_tree(make_grades(3.49), 'INTJ', branches=sample_branches())
        self.assertEqual(result['top_categories'][0]['best_score'], 60.0)
        self.assertNotIn('note', result['top_categories'][0]['branches'][0])
        # MBTI ไม่ใช่สาย T (ตัวที่ 3 เป็น F)
        result = dt.run_decision_tree(make_grades(4.0), 'INFJ', branches=sample_branches())
        for c in result['top_categories']:
            for b in c['branches']:
                self.assertNotIn('note', b)

    def test_interest_mode_has_no_avg_grade_and_no_boost(self):
        riasec = {L: 1.0 for L in dt.RIASEC_LETTERS}
        result = dt.run_decision_tree(None, 'INTJ', riasec=riasec, branches=sample_branches())
        self.assertIsNone(result['avg_grade'])
        self.assertEqual(result['top_categories'][0]['best_score'], 50.0)
        for c in result['top_categories']:
            for b in c['branches']:
                self.assertNotIn('note', b)

    def test_missing_faculty_grouped_under_placeholder(self):
        branches = [make_branch(id=1, faculty=None, mbti_match=('INTJ',)),
                    make_branch(id=2, faculty='', mbti_match=('INTJ',))]
        result = dt.run_decision_tree(make_grades(), 'INTJ', branches=branches)
        self.assertEqual(len(result['top_categories']), 1)
        self.assertEqual(result['top_categories'][0]['faculty'], 'ไม่ระบุคณะ')
        self.assertEqual(len(result['top_categories'][0]['branches']), 2)

    def test_empty_branches_returns_error(self):
        result = dt.run_decision_tree(make_grades(), 'INTJ', branches=[])
        self.assertIn('error', result)

    def test_db_failure_returns_error_dict(self):
        with mock.patch.object(dt, 'get_branches', side_effect=RuntimeError('db down')):
            result = dt.run_decision_tree(make_grades(), 'INTJ')
        self.assertEqual(result, {'error': 'db down'})

    def test_fetches_from_db_when_branches_not_given(self):
        with mock.patch.object(dt, 'get_branches', return_value=sample_branches()) as fetch:
            result = dt.run_decision_tree(make_grades(), 'INTJ')
        fetch.assert_called_once_with()
        self.assertEqual(result['branches_considered'], 10)


# ========================================
# get_db_config / import side effects
# ========================================
class DbConfigTests(unittest.TestCase):

    def test_raises_without_password(self):
        with mock.patch.dict(os.environ, {}, clear=True):
            with self.assertRaises(RuntimeError):
                dt.get_db_config()
        with mock.patch.dict(os.environ, {'MYSQLPASSWORD': ''}):
            with self.assertRaises(RuntimeError):
                dt.get_db_config()

    def test_defaults_and_overrides(self):
        with mock.patch.dict(os.environ, {'MYSQLPASSWORD': 'secret'}, clear=True):
            cfg = dt.get_db_config()
        self.assertEqual(cfg, {'host': '127.0.0.1', 'port': 3306, 'user': 'root',
                               'password': 'secret', 'database': 'futureway'})

        env = {'MYSQLPASSWORD': 'pw', 'MYSQLHOST': 'db.local', 'MYSQLPORT': '3307',
               'MYSQLUSER': 'app', 'MYSQLDATABASE': 'futureway'}
        with mock.patch.dict(os.environ, env, clear=True):
            cfg = dt.get_db_config()
        self.assertEqual(cfg, {'host': 'db.local', 'port': 3307, 'user': 'app',
                               'password': 'pw', 'database': 'futureway'})

    def test_module_has_no_db_config_global(self):
        # การตั้งค่าต้องถูกอ่านตอนเรียก get_db_config() เท่านั้น ไม่ใช่ตอน import
        self.assertFalse(hasattr(dt, 'DB_CONFIG'))


# ========================================
# main()
# ========================================
class MainTests(unittest.TestCase):

    def run_main(self, payload):
        """รัน main() ด้วย stdin ที่กำหนด คืน (exit_code, stdout_text, stderr_text)"""
        stdin  = io.StringIO(payload if isinstance(payload, str) else json.dumps(payload, ensure_ascii=False))
        stdout = io.StringIO()
        stderr = io.StringIO()
        with mock.patch.object(sys, 'stdin', stdin), \
             mock.patch.object(sys, 'stdout', stdout), \
             mock.patch.object(sys, 'stderr', stderr):
            code = dt.main()
        return code, stdout.getvalue(), stderr.getvalue()

    def setUp(self):
        patches = [
            mock.patch.object(dt, 'get_mbti_questions', return_value=MBTI_QUESTIONS),
            mock.patch.object(dt, 'get_riasec_questions', return_value=RIASEC_QUESTIONS),
            mock.patch.object(dt, 'get_branches', return_value=sample_branches()),
        ]
        for p in patches:
            p.start()
            self.addCleanup(p.stop)

    def full_answers(self):
        # E S T J ทุกข้อเลือก A
        return [answer(i, 'A') for i in range(1, 13)]

    def test_grade_mode_with_answers_success(self):
        code, out, err = self.run_main({'grades': make_grades(3.0), 'answers': self.full_answers()})
        self.assertEqual(code, 0)
        self.assertEqual(err, '')
        result = json.loads(out)
        self.assertEqual(result['mbti'], 'ESTJ')
        self.assertEqual(result['avg_grade'], 3.0)
        self.assertEqual(result['branches_considered'], 10)
        self.assertEqual(len(result['top_categories']), 3)
        self.assertEqual(result['mbti_detail']['EI'], {'E': 3, 'I': 0})
        self.assertNotIn('riasec_detail', result)
        # stdout ต้องเป็น JSON บรรทัดเดียว (PHP json_decode ทั้งก้อน)
        self.assertEqual(out.count('\n'), 1)

    def test_legacy_mode_with_precomputed_mbti(self):
        code, out, _ = self.run_main({'grades': make_grades(4.0), 'mbti': 'INTJ'})
        self.assertEqual(code, 0)
        result = json.loads(out)
        self.assertEqual(result['mbti'], 'INTJ')
        self.assertNotIn('mbti_detail', result)
        self.assertEqual(result['top_categories'][0]['best_score'], 65.0)  # science boost

    def test_interest_mode_success(self):
        # I N T J: EI/SN เลือก B, TF/JP เลือก A -> ตรงตำแหน่งแรกของสาขาวิศวะ 0 ในโหมด interest = 50
        answers = [answer(i, 'B') for i in range(1, 7)] + [answer(i, 'A') for i in range(7, 13)]
        code, out, _ = self.run_main({'interests': [1, 2, 3], 'answers': answers})
        self.assertEqual(code, 0)
        result = json.loads(out)
        self.assertEqual(result['mbti'], 'INTJ')
        self.assertIsNone(result['avg_grade'])
        self.assertEqual(result['riasec_detail']['matched'], 3)
        self.assertEqual(result['riasec_detail']['scores']['R'], 1.0)
        self.assertEqual(result['top_categories'][0]['best_score'], 50.0)

    def test_thai_and_emoji_survive_stdout(self):
        code, out, _ = self.run_main({'grades': make_grades(4.0), 'mbti': 'INTJ'})
        self.assertEqual(code, 0)
        self.assertIn('วิศวกรรมศาสตร์', out)   # ensure_ascii=False
        self.assertIn('⭐', out)

    def assert_error(self, payload, contains=None):
        code, out, err = self.run_main(payload)
        self.assertEqual(code, 1)
        result = json.loads(out)
        self.assertEqual(set(result.keys()), {'error'})
        self.assertNotIn('sent_ids', result)
        self.assertTrue(err.strip())
        self.assertIn(result['error'], err)
        if contains:
            self.assertIn(contains, result['error'])
        return result

    def test_missing_grades_and_interests(self):
        self.assert_error({'answers': self.full_answers()}, contains='grades หรือ interests')

    def test_missing_answers_and_mbti(self):
        self.assert_error({'grades': make_grades()}, contains='answers')

    def test_unmatched_mbti_answers(self):
        self.assert_error({'grades': make_grades(), 'answers': [answer(999, 'A')]},
                          contains='mbti_questions')

    def test_unmatched_interests(self):
        self.assert_error({'interests': [999], 'answers': self.full_answers()},
                          contains='riasec_questions')

    def test_invalid_json_input(self):
        self.assert_error('{not json')

    def test_non_object_json_input(self):
        self.assert_error('[1, 2, 3]')

    def test_db_error_from_run_decision_tree(self):
        with mock.patch.object(dt, 'get_branches', side_effect=RuntimeError('db down')):
            self.assert_error({'grades': make_grades(), 'mbti': 'INTJ'}, contains='db down')

    def test_empty_branches_error(self):
        with mock.patch.object(dt, 'get_branches', return_value=[]):
            self.assert_error({'grades': make_grades(), 'mbti': 'INTJ'}, contains='branches')


if __name__ == '__main__':
    unittest.main()
