<?php

namespace App\Http\Controllers;

use App\Models\Group;
use App\Services\BalanceService;
use Illuminate\Http\Request;

class BalanceController extends Controller
{
    public function index(Request $request, Group $group, BalanceService $balanceService)
    {
        if (! $group->members->contains($request->user()->id)) {
            return response()->json(['message' => 'غير مصرّح لك.'], 403);
        }

        $balances = $balanceService->calculateBalances($group);

        return response()->json([
            'group'    => $group->name,
            'balances' => $balances,
        ]);
    }

    public function simplify(Request $request, Group $group, BalanceService $balanceService)
    {
        if (! $group->members->contains($request->user()->id)) {
            return response()->json(['message' => 'غير مصرّح لك.'], 403);
        }

        $transactions = $balanceService->simplifyDebts($group);

        return response()->json([
            'group'        => $group->name,
            'transactions' => $transactions,
        ]);
    }
}