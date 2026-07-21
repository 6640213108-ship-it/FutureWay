#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
FutureWay - Decision Tree
รับ input: เกรด 6 วิชา + MBTI type
ส่ง output: JSON สาขาที่แนะนำ 3 อันดับ
"""

import sys
import os

# บังคับให้ stdout/stderr เป็น UTF-8 เสมอ ไม่ว่า console/PHP proc_open
# จะรันด้วย encoding อะไรก็ตาม (แก้ปัญหา 'charmap' codec can't encode
# ตอน print ตัวอักษรไทยหรืออีโมจิ เช่น ⭐ ออกไปให้ PHP อ่าน)
sys.stdout.reconfigure(encoding='utf-8')
sys.stderr.reconfigure(encoding='utf-8')

sys.path.insert(0, os.path.join(os.path.dirname(os.path.abspath(__file__)), 'python_libs'))
import json
import mysql.connector

# ========================================
# ตั้งค่าเชื่อมต่อ Database
# ========================================
DB_CONFIG = {
    'host':     'mysql.railway.internal',
    'port':     3306,
    'user':     'root',
    'password': 'OLdaGruletpcPRSKSZkUOUrKaUWmDjri',
    'database': 'railway'      # ต้องตรงกับ DB ที่ไฟล์ PHP ทุกไฟล์ใช้ (railway)
}

# ========================================
# MBTI Decision Tree (หัวข้อที่ 3)
# ========================================
def get_mbti_questions():
    """
    ดึงคำถาม MBTI ทั้งหมดจากตาราง mbti_questions
    โครงสร้างตาราง: id, category (EI/SN/TF/JP), question_no, question_text,
                    option_a_text, option_a_trait, option_b_text, option_b_trait
    """
    conn   = mysql.connector.connect(**DB_CONFIG)
    cursor = conn.cursor(dictionary=True)
    cursor.execute("SELECT * FROM mbti_questions ORDER BY category, question_no")
    questions = cursor.fetchall()
    cursor.close()
    conn.close()
    return questions


def resolve_mbti_from_answers(answers):
    """
    รับคำตอบของผู้ใช้ แล้วคำนวณรหัส MBTI 4 ตัวอักษร
    ตามหลัก Decision Tree (นับคะแนนเสียงข้างมากในแต่ละมิติ EI / SN / TF / JP)

    answers: list of dict เช่น
        [{'question_id': 1, 'selected': 'A'}, {'question_id': 2, 'selected': 'B'}, ...]
        - question_id ต้องตรงกับ id ในตาราง mbti_questions
        - selected คือ 'A' หรือ 'B' (ข้อที่ผู้ใช้เลือก)

    คืนค่า: {
        'mbti': 'INTJ',
        'detail': {'EI': {'E': 1, 'I': 2}, 'SN': {...}, 'TF': {...}, 'JP': {...}}
    }
    """
    questions = get_mbti_questions()
    q_map = {q['id']: q for q in questions}

    # นับคะแนนแยกตามมิติ
    tally = {
        'EI': {'E': 0, 'I': 0},
        'SN': {'S': 0, 'N': 0},
        'TF': {'T': 0, 'F': 0},
        'JP': {'J': 0, 'P': 0},
    }

    for ans in answers:
        q = q_map.get(ans['question_id'])
        if not q:
            continue  # ข้ามคำถามที่ไม่พบในฐานข้อมูล

        category = q['category']            # 'EI' / 'SN' / 'TF' / 'JP'
        selected = str(ans['selected']).strip().upper()   # 'A' หรือ 'B'

        if selected == 'A':
            trait = q['option_a_trait']
        elif selected == 'B':
            trait = q['option_b_trait']
        else:
            continue

        if category in tally and trait in tally[category]:
            tally[category][trait] += 1

    # ทางแยกตัดสินใจ (Decision Tree) ของแต่ละมิติ: เลือกตัวอักษรที่ได้คะแนนมากกว่า
    # ถ้าคะแนนเท่ากันพอดี (tie) จะ default ไปทางฝั่งขวาของมิตินั้น (I, N, F, P)
    tie_break = {'EI': 'I', 'SN': 'N', 'TF': 'F', 'JP': 'P'}

    mbti_code = ''
    for dim in ['EI', 'SN', 'TF', 'JP']:
        counts  = tally[dim]
        letters = list(counts.keys())  # เช่น ['E', 'I']

        if counts[letters[0]] > counts[letters[1]]:
            result_letter = letters[0]
        elif counts[letters[1]] > counts[letters[0]]:
            result_letter = letters[1]
        else:
            result_letter = tie_break[dim]

        mbti_code += result_letter

    return {
        'mbti':   mbti_code,
        'detail': tally
    }


# ========================================
# Decision Tree Logic
# ========================================
def calculate_score(branch, grades, mbti):
    """
    คำนวณคะแนนความเหมาะสมของสาขา (0-100)
    
    สูตร:
    1. เช็ค MBTI match → ถ้าไม่ match หัก 30 คะแนน
    2. เช็คเกรดขั้นต่ำ → ถ้าไม่ถึงขั้นต่ำ หักคะแนน
    3. คำนวณ weighted grade score
    """
    score = 0
    
    # --- Step 1: MBTI Score (40 คะแนน) ---
    mbti_match = json.loads(branch['mbti_match']) if isinstance(branch['mbti_match'], str) else branch['mbti_match']
    
    if mbti in mbti_match:
        score += 40  # match เต็ม
    else:
        # เช็คว่า match บางมิติไหม
        partial = 0
        for m in mbti_match:
            match_count = sum(1 for a, b in zip(mbti, m) if a == b)
            partial = max(partial, match_count)
        score += (partial / 4) * 25  # match บางส่วน ได้สูงสุด 25

    # --- Step 2: เช็คเกรดขั้นต่ำ ---
    grade_keys = ['math', 'sci', 'eng', 'thai', 'social', 'art']
    min_keys   = ['min_math', 'min_sci', 'min_eng', 'min_thai', 'min_social', 'min_art']
    
    below_min = False
    for gk, mk in zip(grade_keys, min_keys):
        min_val = float(branch[mk])
        if min_val > 0 and float(grades[gk]) < min_val:
            below_min = True
            score -= 15  # หักคะแนนถ้าเกรดต่ำกว่าขั้นต่ำ

    # --- Step 3: Weighted Grade Score (60 คะแนน) ---
    weight_keys = ['weight_math', 'weight_sci', 'weight_eng', 
                   'weight_thai', 'weight_social', 'weight_art']
    
    total_weight    = sum(float(branch[wk]) for wk in weight_keys)
    weighted_score  = 0
    
    for gk, wk in zip(grade_keys, weight_keys):
        grade  = float(grades[gk])
        weight = float(branch[wk])
        weighted_score += (grade / 4.0) * weight  # normalize เป็น 0-1
    
    if total_weight > 0:
        score += (weighted_score / total_weight) * 60

    return round(max(0, min(100, score)), 2)


def run_decision_tree(grades, mbti):
    """
    รัน Decision Tree หลัก
    ส่งคืน top 3 สาขาที่เหมาะสมที่สุด
    """
    try:
        conn   = mysql.connector.connect(**DB_CONFIG)
        cursor = conn.cursor(dictionary=True)
        
        cursor.execute("SELECT * FROM branches WHERE is_active = 1")
        branches = cursor.fetchall()
        cursor.close()
        conn.close()
        
    except Exception as e:
        return {"error": str(e)}

    if not branches:
        return {"error": "ไม่พบสาขาในตาราง branches (ตารางว่าง หรือไม่มีแถวที่ is_active = 1)"}

    # คำนวณคะแนนทุกสาขา
    results = []
    for branch in branches:
        score = calculate_score(branch, grades, mbti)
        results.append({
            'id':          branch['id'],
            'name':        branch['name'],
            'faculty':     branch['faculty'],
            'description': branch['description'],
            'score':       score
        })

    # เรียงคะแนนจากมากไปน้อย เอา top 3
    results.sort(key=lambda x: x['score'], reverse=True)
    top3 = results[:3]

    # Decision Tree Rule เพิ่มเติม (ปรับ label)
    avg_grade = sum(float(grades[k]) for k in grades) / len(grades)
    
    # ถ้าเกรดเฉลี่ยสูงมาก (≥ 3.5) และ MBTI เป็นสาย T → boost สายวิทย์
    if avg_grade >= 3.5 and mbti[2] == 'T':
        for r in top3:
            if r['faculty'] in ['วิศวกรรมศาสตร์', 'แพทยศาสตร์', 'วิทยาศาสตร์']:
                r['score'] = min(100, r['score'] + 5)
                r['note']  = '⭐ เกรดดีและบุคลิกเหมาะมาก'

    return {
        'mbti':      mbti,
        'avg_grade': round(avg_grade, 2),
        'top3':      top3
    }


# ========================================
# Main — รับ argument จาก PHP
# ========================================
if __name__ == '__main__':
    try:
        # รับ JSON จาก PHP ผ่าน stdin
        input_data = sys.stdin.read()
        data       = json.loads(input_data)
        
        grades = data['grades']  # {'math': 3.5, 'sci': 3.0, ...}

        mbti_detail = None

        if 'answers' in data:
            # โหมดใหม่: รับคำตอบดิบ [{'question_id':1,'selected':'A'}, ...]
            # แล้วคำนวณรหัส MBTI เองด้วย Decision Tree (หัวข้อที่ 3)
            mbti_result = resolve_mbti_from_answers(data['answers'])
            mbti        = mbti_result['mbti']
            mbti_detail = mbti_result['detail']
        else:
            # โหมดเดิม: รับรหัส MBTI ที่คำนวณมาแล้ว เช่น 'INTJ'
            mbti = data['mbti']

        result = run_decision_tree(grades, mbti)

        if mbti_detail is not None:
            result['mbti_detail'] = mbti_detail
        
        # ส่ง JSON กลับไปให้ PHP
        print(json.dumps(result, ensure_ascii=False))
        
    except Exception as e:
        print(json.dumps({'error': str(e)}, ensure_ascii=False))
