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
        // ضمّ التسويات
        $settlements = $group->settlements()->get();

        foreach ($settlements as $settlement) {
            $balances[$settlement->payer_id]['paid']     += $settlement->amount;
            $balances[$settlement->receiver_id]['owed']  += $settlement->amount;
        }

        // ٣. احسب الرصيد النهائي
        foreach ($balances as &$b) {
            $b['balance'] = round($b['paid'] - $b['owed'], 2);
        }

        return array_values($balances);
    }

    /**
     * يحوّل الأرصدة لقائمة تحويلات مبسّطة (أقل عدد تحويلات).
     */
    public function simplifyDebts(Group $group): array
    {
        $balances = $this->calculateBalances($group);

        // ١. افصل المدينين عن الدائنين (تجاهل اللي رصيده صفر)
        $debtors   = [];
        $creditors = [];

        foreach ($balances as $b) {
            if ($b['balance'] < 0) {
                $debtors[] = ['user_id' => $b['user_id'], 'name' => $b['name'], 'amount' => -$b['balance']];
            } elseif ($b['balance'] > 0) {
                $creditors[] = ['user_id' => $b['user_id'], 'name' => $b['name'], 'amount' => $b['balance']];
            }
        }

        // ٢. رتّب تنازلياً (الأكبر أول)
        usort($debtors, fn($a, $b) => $b['amount'] <=> $a['amount']);
        usort($creditors, fn($a, $b) => $b['amount'] <=> $a['amount']);

        // ٣. طابق أكبر مدين بأكبر دائن
        $transactions = [];
        $i = 0;
        $j = 0;

        while ($i < count($debtors) && $j < count($creditors)) {
            $debtor   = &$debtors[$i];
            $creditor = &$creditors[$j];

            $amount = round(min($debtor['amount'], $creditor['amount']), 2);

            $transactions[] = [
                'from'    => $debtor['name'],
                'from_id' => $debtor['user_id'],
                'to'      => $creditor['name'],
                'to_id'   => $creditor['user_id'],
                'amount'  => $amount,
            ];

            $debtor['amount']   = round($debtor['amount'] - $amount, 2);
            $creditor['amount'] = round($creditor['amount'] - $amount, 2);

            if ($debtor['amount'] <= 0) $i++;
            if ($creditor['amount'] <= 0) $j++;
        }

        return $transactions;
    }
}