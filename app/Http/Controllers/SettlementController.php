<?php

namespace App\Http\Controllers;

use App\Models\Group;
use App\Models\Settlement;
use Illuminate\Http\Request;

class SettlementController extends Controller
{
    public function index(Request $request, Group $group)
    {
        if (! $group->members->contains($request->user()->id)) {
            return response()->json(['message' => 'غير مصرّح لك.'], 403);
        }

        $settlements = $group->settlements()->with(['payer', 'receiver'])->latest()->get();

        return response()->json($settlements);
    }

    public function store(Request $request, Group $group)
    {
        if (! $group->members->contains($request->user()->id)) {
            return response()->json(['message' => 'غير مصرّح لك.'], 403);
        }

        $validated = $request->validate([
            'payer_id'    => 'required|exists:users,id',
            'receiver_id' => 'required|exists:users,id|different:payer_id',
            'amount'      => 'required|numeric|min:0.01',
        ]);

        if (! $group->members->contains($validated['payer_id']) ||
            ! $group->members->contains($validated['receiver_id'])) {
            return response()->json(['message' => 'الطرفان يجب أن يكونا أعضاء في المجموعة.'], 422);
        }

        $settlement = Settlement::create([
            'group_id'    => $group->id,
            'payer_id'    => $validated['payer_id'],
            'receiver_id' => $validated['receiver_id'],
            'amount'      => $validated['amount'],
        ]);

        return response()->json($settlement->load(['payer', 'receiver']), 201);
    }

    public function destroy(Request $request, Group $group, Settlement $settlement)
    {
        if (! $group->members->contains($request->user()->id)) {
            return response()->json(['message' => 'غير مصرّح لك.'], 403);
        }

        $settlement->delete();

        return response()->json(['message' => 'تم حذف التسوية.']);
    }
}