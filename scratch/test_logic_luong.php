<?php
/**
 * Test logic tính lương mới:
 * - >= 28 ngày làm: Lương = LCB (hoặc trung bình trọng số nếu nhiều chức vụ)
 * - < 28 ngày làm: Lương = Số ngày làm * (LCB / 30)
 * - Phụ cấp, thưởng, khấu trừ: cộng/trừ bình thường
 */

function calculateNewSalary($workingDaysByPos, $totalWorkingDays) {
    if ($totalWorkingDays >= 28) {
        // Trường hợp đủ 28 ngày -> Trình bày như Lương đầy đủ
        // Nếu nhiều chức vụ, tính trung bình trọng số
        $weightedSum = 0;
        foreach ($workingDaysByPos as $pos) {
            $weightedSum += ($pos['days'] / $totalWorkingDays) * $pos['base_salary'];
        }
        return round($weightedSum);
    } else {
        // Trường hợp không đủ 28 ngày -> Pro-rate theo 1/30
        $sum = 0;
        foreach ($workingDaysByPos as $pos) {
            $sum += ($pos['days'] * $pos['base_salary'] / 30);
        }
        return round($sum);
    }
}

// Case 1: Làm 28 ngày, 1 chức vụ (LCB 15M)
$case1 = calculateNewSalary([['days' => 28, 'base_salary' => 15000000]], 28);
echo "Case 1 (28 days, 1 pos): " . number_format($case1) . " (Expected: 15,000,000)\n";

// Case 2: Làm 27 ngày, 1 chức vụ (LCB 15M)
$case2 = calculateNewSalary([['days' => 27, 'base_salary' => 15000000]], 27);
echo "Case 2 (27 days, 1 pos): " . number_format($case2) . " (Expected: 13,500,000)\n";

// Case 3: Làm 30 ngày, 2 chức vụ (15 ngày 10M, 15 ngày 20M)
$case3 = calculateNewSalary([
    ['days' => 15, 'base_salary' => 10000000],
    ['days' => 15, 'base_salary' => 20000000]
], 30);
echo "Case 3 (30 days, 2 pos): " . number_format($case3) . " (Expected: 15,000,000 - Average)\n";

// Case 4: Làm 10 ngày, 2 chức vụ (5 ngày 10M, 5 ngày 20M)
$case4 = calculateNewSalary([
    ['days' => 5, 'base_salary' => 10000000],
    ['days' => 5, 'base_salary' => 20000000]
], 10);
$expected4 = (5 * 10000000 / 30) + (5 * 20000000 / 30);
echo "Case 4 (10 days, 2 pos): " . number_format($case4) . " (Expected: " . number_format($expected4) . ")\n";
