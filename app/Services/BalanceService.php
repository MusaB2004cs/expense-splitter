<?php

namespace App\Services;

use App\Models\Group;

class BalanceService
{
    /**
     * يحسب رصيد كل عضو في المجموعة.
     * موجب = له / سالب = عليه.
     */
    public function calculateBalances(Group $group): array
    {
        $balances = [];

        // ١. ابدأ كل الأعضاء برصيد صفر
        foreach ($group->members as $member) {
            $balances[$member->id] = [
                'user_id' => $member->id,
                'name'    => $member->name,
                'paid'    => 0,
                'owed'    => 0,
                'balance' => 0,
            ];
        }

        // ٢. مرّ على كل مصروف واحسب
        $expenses = $group->expenses()->with('shares')->get();

        foreach ($expenses as $expense) {
            // اللي دفع
            $balances[$expense->paid_by]['paid'] += $expense->amount;

            // أنصبة كل عضو
            foreach ($expense->shares as $share) {
                $balances[$share->user_id]['owed'] += $share->share_amount;
            }
        }

        // ٣. احسب الرصيد النهائي
        foreach ($balances as &$b) {
            $b['balance'] = round($b['paid'] - $b['owed'], 2);
        }

        return array_values($balances);
    }
}