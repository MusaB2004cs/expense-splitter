<?php

namespace App\Http\Controllers;

use App\Models\Expense;
use App\Models\ExpenseShare;
use App\Models\Group;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ExpenseController extends Controller
{
    public function index(Request $request, Group $group)
    {
        if (! $group->members->contains($request->user()->id)) {
            return response()->json(['message' => 'غير مصرّح لك.'], 403);
        }

        $expenses = $group->expenses()->with(['payer', 'shares.user'])->latest()->get();

        return response()->json($expenses);
    }

    public function store(Request $request, Group $group)
    {
        if (! $group->members->contains($request->user()->id)) {
            return response()->json(['message' => 'غير مصرّح لك.'], 403);
        }

        $validated = $request->validate([
            'description' => 'required|string|max:255',
            'amount'      => 'required|numeric|min:0.01',
            'paid_by'     => 'required|exists:users,id',
        ]);

        if (! $group->members->contains($validated['paid_by'])) {
            return response()->json(['message' => 'الدافع يجب أن يكون عضواً في المجموعة.'], 422);
        }

        $expense = DB::transaction(function () use ($group, $validated) {
            $expense = Expense::create([
                'group_id'    => $group->id,
                'paid_by'     => $validated['paid_by'],
                'description' => $validated['description'],
                'amount'      => $validated['amount'],
            ]);

            $members     = $group->members;
            $memberCount = $members->count();
            $shareAmount = round($validated['amount'] / $memberCount, 2);

            foreach ($members as $member) {
                ExpenseShare::create([
                    'expense_id'   => $expense->id,
                    'user_id'      => $member->id,
                    'share_amount' => $shareAmount,
                ]);
            }

            return $expense;
        });

        return response()->json($expense->load(['payer', 'shares.user']), 201);
    }

    public function show(Request $request, Group $group, Expense $expense)
    {
        if (! $group->members->contains($request->user()->id)) {
            return response()->json(['message' => 'غير مصرّح لك.'], 403);
        }

        return response()->json($expense->load(['payer', 'shares.user']));
    }

    public function update(Request $request, Group $group, Expense $expense)
    {
        if (! $group->members->contains($request->user()->id)) {
            return response()->json(['message' => 'غير مصرّح لك.'], 403);
        }

        $validated = $request->validate([
            'description' => 'sometimes|string|max:255',
            'amount'      => 'sometimes|numeric|min:0.01',
        ]);

        DB::transaction(function () use ($expense, $validated, $group) {
            $expense->update($validated);

            if (isset($validated['amount'])) {
                $expense->shares()->delete();

                $members     = $group->members;
                $shareAmount = round($validated['amount'] / $members->count(), 2);

                foreach ($members as $member) {
                    ExpenseShare::create([
                        'expense_id'   => $expense->id,
                        'user_id'      => $member->id,
                        'share_amount' => $shareAmount,
                    ]);
                }
            }
        });

        return response()->json($expense->load(['payer', 'shares.user']));
    }

    public function destroy(Request $request, Group $group, Expense $expense)
    {
        if (! $group->members->contains($request->user()->id)) {
            return response()->json(['message' => 'غير مصرّح لك.'], 403);
        }

        $expense->delete();

        return response()->json(['message' => 'تم حذف المصروف.']);
    }
}