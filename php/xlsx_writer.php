<?php
// ========================================
// FutureWay - xlsx_writer.php
// สร้างไฟล์ Excel (.xlsx) ด้วย PHP ล้วน ๆ ไม่ต้องลง library เพิ่ม
//
// ทำไมไม่ใช้ PhpSpreadsheet: โปรเจกต์นี้ไม่ได้รัน composer install ตอน build
// (Dockerfile copy โค้ดขึ้นไปตรง ๆ) การเพิ่ม dependency แปลว่าต้องแก้ build ทั้งชุด
//
// ทำไมไม่ใช่ CSV เปลี่ยนนามสกุลเป็น .xls: Excel รุ่นใหม่จะเตือน "รูปแบบไฟล์ไม่ตรงกับนามสกุล"
// ทุกครั้งที่เปิด และทำได้แค่ชีตเดียว ไม่มีหัวตารางตรึง ไม่มีตัวกรอง
//
// ไฟล์ .xlsx จริง ๆ คือไฟล์ ZIP ที่ข้างในเป็น XML หลายไฟล์ตามมาตรฐาน OOXML
// ไฟล์นี้จึงมี 2 ส่วน: ตัวประกอบ XML และตัวเขียน ZIP (ไม่ใช้ ext-zip ที่ image
// php:8.3-apache ไม่ได้เปิดมาให้)
// ========================================

/**
 * แปลงเลขคอลัมน์ (1-based) เป็นตัวอักษรแบบ Excel: 1 -> A, 27 -> AA
 */
function xlsxColumnLetter(int $index): string {
    $letter = '';
    while ($index > 0) {
        $rem    = ($index - 1) % 26;
        $letter = chr(65 + $rem) . $letter;
        $index  = (int)(($index - $rem - 1) / 26);
    }
    return $letter;
}

/**
 * escape ข้อความให้ปลอดภัยสำหรับ XML
 * ตัดอักขระควบคุมที่ XML 1.0 ไม่ยอมรับออกด้วย (เจอแล้ว Excel จะฟ้องว่าไฟล์เสีย)
 */
function xlsxEscape(string $text): string {
    $clean = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F]/u', '', $text);

    // /u จะคืน null ถ้าข้อความไม่ใช่ UTF-8 ที่ถูกต้อง (ข้อมูลเก่าที่เคยบันทึกผิด encoding)
    // ต้องลองใหม่แบบไม่สน encoding ไม่ใช่ปล่อยให้กลายเป็นค่าว่าง เพราะจะทำให้
    // เซลล์นั้นหายไปเงียบ ๆ โดยไม่มีใครรู้ว่าข้อมูลตกหล่น
    if ($clean === null) {
        $clean = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F]/', '', $text) ?? '';
    }

    // ENT_SUBSTITUTE = ตัวอักษรที่ decode ไม่ได้กลายเป็น U+FFFD แทนที่จะทิ้งทั้งสตริง
    return htmlspecialchars($clean, ENT_QUOTES | ENT_XML1 | ENT_SUBSTITUTE, 'UTF-8');
}

/**
 * สร้าง XML ของ 1 ชีต
 *
 * @param array $rows  แถวข้อมูล แต่ละแถวเป็น array ของค่าในเซลล์
 *                     ค่าที่เป็น int/float จะถูกเขียนเป็นตัวเลข (คำนวณใน Excel ได้)
 *                     ค่าอื่นเขียนเป็นข้อความ
 * @param array $widths ความกว้างคอลัมน์ (หน่วยตามที่ Excel ใช้) เว้นว่างได้
 * @param bool  $headerRow แถวแรกเป็นหัวตารางไหม (จะทำพื้นม่วง ตัวหนา ตรึงแถว และใส่ตัวกรอง)
 */
function xlsxSheetXml(array $rows, array $widths = [], bool $headerRow = true): string {
    $maxCols = 0;
    foreach ($rows as $r) {
        $maxCols = max($maxCols, count($r));
    }
    $rowCount = count($rows);

    $xml  = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' . "\n";
    $xml .= '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">';

    // ตรึงแถวหัวตารางไว้ เลื่อนดูข้อมูลยาว ๆ แล้วยังเห็นว่าคอลัมน์ไหนคืออะไร
    if ($headerRow && $rowCount > 1) {
        $xml .= '<sheetViews><sheetView workbookViewId="0">'
              . '<pane ySplit="1" topLeftCell="A2" activePane="bottomLeft" state="frozen"/>'
              . '</sheetView></sheetViews>';
    }

    if ($widths) {
        $xml .= '<cols>';
        foreach ($widths as $i => $w) {
            $n    = $i + 1;
            $xml .= '<col min="' . $n . '" max="' . $n . '" width="' . $w . '" customWidth="1"/>';
        }
        $xml .= '</cols>';
    }

    $xml .= '<sheetData>';
    foreach ($rows as $rowIndex => $cells) {
        $rowNo = $rowIndex + 1;
        $xml  .= '<row r="' . $rowNo . '">';

        foreach (array_values($cells) as $colIndex => $value) {
            $ref   = xlsxColumnLetter($colIndex + 1) . $rowNo;
            $style = ($headerRow && $rowIndex === 0) ? ' s="1"' : '';

            if (is_int($value) || is_float($value)) {
                $xml .= '<c r="' . $ref . '"' . $style . '><v>' . $value . '</v></c>';
            } else {
                $text = xlsxEscape((string)$value);
                if ($text === '') {
                    $xml .= '<c r="' . $ref . '"' . $style . '/>';
                } else {
                    // inlineStr = เก็บข้อความไว้ในเซลล์เลย ไม่ต้องมีไฟล์ sharedStrings แยก
                    // xml:space="preserve" กันช่องว่างหัว-ท้ายหาย
                    $xml .= '<c r="' . $ref . '"' . $style . ' t="inlineStr"><is><t xml:space="preserve">'
                          . $text . '</t></is></c>';
                }
            }
        }
        $xml .= '</row>';
    }
    $xml .= '</sheetData>';

    // ปุ่มกรอง/เรียงบนหัวตาราง (ที่เห็นเป็นลูกศรสามเหลี่ยมมุมเซลล์)
    if ($headerRow && $rowCount > 1 && $maxCols > 0) {
        $xml .= '<autoFilter ref="A1:' . xlsxColumnLetter($maxCols) . $rowCount . '"/>';
    }

    return $xml . '</worksheet>';
}

/**
 * ตาราง style ของ workbook — มีแค่ 2 แบบพอ
 *   index 0 = ปกติ
 *   index 1 = หัวตาราง (ตัวหนา ตัวอักษรขาว พื้นม่วงสีเดียวกับธีมเว็บ)
 *
 * fills 2 ตัวแรกต้องเป็น none กับ gray125 เสมอ เป็นข้อกำหนดของ Excel
 * ถ้าข้ามไปจะเปิดไฟล์ไม่ขึ้น
 */
function xlsxStylesXml(): string {
    return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' . "\n"
        . '<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
        . '<fonts count="2">'
        . '<font><sz val="11"/><name val="Tahoma"/></font>'
        . '<font><b/><sz val="11"/><color rgb="FFFFFFFF"/><name val="Tahoma"/></font>'
        . '</fonts>'
        . '<fills count="3">'
        . '<fill><patternFill patternType="none"/></fill>'
        . '<fill><patternFill patternType="gray125"/></fill>'
        . '<fill><patternFill patternType="solid"><fgColor rgb="FF7B2FF7"/><bgColor indexed="64"/></patternFill></fill>'
        . '</fills>'
        . '<borders count="1"><border><left/><right/><top/><bottom/><diagonal/></border></borders>'
        . '<cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>'
        . '<cellXfs count="2">'
        . '<xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/>'
        . '<xf numFmtId="0" fontId="1" fillId="2" borderId="0" xfId="0" applyFont="1" applyFill="1" applyAlignment="1">'
        . '<alignment vertical="center" wrapText="1"/></xf>'
        . '</cellXfs>'
        // ต้องมี cellStyles ไม่งั้นโปรแกรมอ่าน xlsx บางตัวจะเตือนว่า workbook ไม่มี default style
        // ลำดับ element ตาม schema บังคับ: fonts -> fills -> borders -> cellStyleXfs -> cellXfs -> cellStyles
        . '<cellStyles count="1"><cellStyle name="Normal" xfId="0" builtinId="0"/></cellStyles>'
        . '</styleSheet>';
}

/**
 * รวมทุกไฟล์เป็น ZIP 1 ก้อน (คือตัวไฟล์ .xlsx)
 *
 * เขียน ZIP เองเพราะ ext-zip (คลาส ZipArchive) ไม่ได้ถูกเปิดใน image php:8.3-apache
 * และไม่อยากให้ต้องแก้ Dockerfile ลง libzip เพิ่มแค่เพื่อฟีเจอร์เดียว
 *
 * @param array<string,string> $files  ชื่อไฟล์ในzip => เนื้อหา
 */
function xlsxZip(array $files): string {
    $now     = getdate();
    $dosTime = ($now['hours'] << 11) | ($now['minutes'] << 5) | ((int)($now['seconds'] / 2));
    $dosDate = (($now['year'] - 1980) << 9) | ($now['mon'] << 5) | $now['mday'];

    $local   = '';
    $central = '';
    $offset  = 0;

    foreach ($files as $name => $data) {
        $crc  = crc32($data);
        $size = strlen($data);

        // ย่อด้วย deflate ถ้าทำได้ (zlib มากับ PHP อยู่แล้ว) ไม่ได้ก็เก็บดิบ ๆ
        $packed = function_exists('gzdeflate') ? gzdeflate($data, 6) : false;
        if ($packed === false) {
            $packed = $data;
            $method = 0;   // stored
        } else {
            $method = 8;   // deflated
        }
        $packedSize = strlen($packed);

        // flag bit 11 (0x0800) = ชื่อไฟล์เป็น UTF-8
        $header = pack('v', 20) . pack('v', 0x0800) . pack('v', $method)
                . pack('v', $dosTime) . pack('v', $dosDate)
                . pack('V', $crc) . pack('V', $packedSize) . pack('V', $size)
                . pack('v', strlen($name)) . pack('v', 0);

        $local .= "PK\x03\x04" . $header . $name . $packed;

        $central .= "PK\x01\x02" . pack('v', 20) . $header
                  . pack('v', 0)          // comment length
                  . pack('v', 0)          // disk number
                  . pack('v', 0)          // internal attributes
                  . pack('V', 32)         // external attributes (archive)
                  . pack('V', $offset)
                  . $name;

        $offset = strlen($local);
    }

    $count = count($files);
    $end   = "PK\x05\x06" . pack('v', 0) . pack('v', 0)
           . pack('v', $count) . pack('v', $count)
           . pack('V', strlen($central)) . pack('V', strlen($local))
           . pack('v', 0);

    return $local . $central . $end;
}

/**
 * ประกอบไฟล์ .xlsx ให้พร้อมส่งออก
 *
 * @param array $sheets  [ ['name' => 'ชื่อชีต', 'rows' => [...], 'widths' => [...]], ... ]
 * @return string        เนื้อหาไฟล์ .xlsx
 */
function buildXlsx(array $sheets): string {
    $files = [];

    $contentTypes = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' . "\n"
        . '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
        . '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
        . '<Default Extension="xml" ContentType="application/xml"/>'
        . '<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'
        . '<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>';

    $sheetTags = '';
    $relTags   = '';

    foreach (array_values($sheets) as $i => $sheet) {
        $n = $i + 1;

        // ชื่อชีตห้ามยาวเกิน 31 ตัว และห้ามมี : \ / ? * [ ]
        $name = mb_substr(str_replace([':', '\\', '/', '?', '*', '[', ']'], '', $sheet['name']), 0, 31);

        $files["xl/worksheets/sheet$n.xml"] = xlsxSheetXml(
            $sheet['rows'],
            $sheet['widths'] ?? [],
            $sheet['header'] ?? true
        );

        $contentTypes .= '<Override PartName="/xl/worksheets/sheet' . $n . '.xml"'
                       . ' ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>';
        $sheetTags    .= '<sheet name="' . xlsxEscape($name) . '" sheetId="' . $n . '" r:id="rId' . $n . '"/>';
        $relTags      .= '<Relationship Id="rId' . $n . '"'
                       . ' Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet"'
                       . ' Target="worksheets/sheet' . $n . '.xml"/>';
    }

    $contentTypes .= '</Types>';

    // rId ของ styles ต้องไม่ชนกับของชีต เลยเริ่มที่ 100
    $relTags .= '<Relationship Id="rId100"'
              . ' Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles"'
              . ' Target="styles.xml"/>';

    $files['[Content_Types].xml'] = $contentTypes;

    $files['_rels/.rels'] = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' . "\n"
        . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
        . '<Relationship Id="rId1"'
        . ' Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument"'
        . ' Target="xl/workbook.xml"/>'
        . '</Relationships>';

    $files['xl/workbook.xml'] = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' . "\n"
        . '<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"'
        . ' xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
        . '<sheets>' . $sheetTags . '</sheets>'
        . '</workbook>';

    $files['xl/_rels/workbook.xml.rels'] = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' . "\n"
        . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
        . $relTags
        . '</Relationships>';

    $files['xl/styles.xml'] = xlsxStylesXml();

    return xlsxZip($files);
}
