<?php

namespace App\Http\Controllers;

use App\Http\Resources\GroupResource;
use App\Models\Group;
use App\Models\User;
use Illuminate\Http\Request;

class GroupController extends Controller
{
    /**
     * عرض مجموعاتي
     *
     * ترجع كل المجموعات التي ينتمي إليها المستخدم الحالي.
     * @group Groups
     * @authenticated
     */
    public function index(Request $request)
    {
        $groups = $request->user()->groups()->with('members')->get();

        return GroupResource::collection($groups);
    }

    /**
     * إنشاء مجموعة
     *
     * ينشئ مجموعة جديدة ويضيف المُنشئ كعضو تلقائياً.
     * @group Groups
     * @authenticated
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'        => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        $group = Group::create([
            'name'        => $validated['name'],
            'description' => $validated['description'] ?? null,
            'created_by'  => $request->user()->id,
        ]);

        $group->members()->attach($request->user()->id);

        return response()->json(new GroupResource($group->load('members')), 201);
    }

    /**
     * عرض مجموعة
     *
     * ترجع تفاصيل مجموعة واحدة مع أعضائها ومصاريفها (للأعضاء فقط).
     * @group Groups
     * @authenticated
     */
    public function show(Request $request, Group $group)
    {
        if (! $group->members->contains($request->user()->id)) {
            return response()->json(['message' => 'غير مصرّح لك بالوصول لهذه المجموعة.'], 403);
        }

        return response()->json(new GroupResource($group->load(['members', 'expenses'])));
    }

    /**
     * تعديل مجموعة
     *
     * يعدّل اسم أو وصف المجموعة (للمُنشئ فقط).
     * @group Groups
     * @authenticated
     */
    public function update(Request $request, Group $group)
    {
        if ($group->created_by !== $request->user()->id) {
            return response()->json(['message' => 'فقط منشئ المجموعة يمكنه التعديل.'], 403);
        }

        $validated = $request->validate([
            'name'        => 'sometimes|string|max:255',
            'description' => 'nullable|string',
        ]);

        $group->update($validated);

        return response()->json(new GroupResource($group));
    }

    /**
     * حذف مجموعة
     *
     * يحذف المجموعة نهائياً (للمُنشئ فقط).
     * @group Groups
     * @authenticated
     */
    public function destroy(Request $request, Group $group)
    {
        if ($group->created_by !== $request->user()->id) {
            return response()->json(['message' => 'فقط منشئ المجموعة يمكنه الحذف.'], 403);
        }

        $group->delete();

        return response()->json(['message' => 'تم حذف المجموعة.']);
    }

    /**
     * إضافة عضو
     *
     * يضيف مستخدماً للمجموعة عبر بريده الإلكتروني (للمُنشئ فقط).
     * @group Groups
     * @authenticated
     */
    public function addMember(Request $request, Group $group)
    {
        if ($group->created_by !== $request->user()->id) {
            return response()->json(['message' => 'فقط المُنشئ يضيف أعضاء.'], 403);
        }

        $validated = $request->validate([
            'email' => 'required|email|exists:users,email',
        ]);

        $user = User::where('email', $validated['email'])->first();

        if ($group->members->contains($user->id)) {
            return response()->json(['message' => 'هذا المستخدم عضو بالفعل.'], 422);
        }

        $group->members()->attach($user->id);

        return response()->json([
            'message' => 'تمت إضافة العضو.',
            'group'   => new GroupResource($group->load('members')),
        ]);
    }

    /**
     * إزالة عضو
     *
     * يزيل عضواً من المجموعة، ولا يمكن إزالة المُنشئ (للمُنشئ فقط).
     * @group Groups
     * @authenticated
     */
    public function removeMember(Request $request, Group $group, User $user)
    {
        if ($group->created_by !== $request->user()->id) {
            return response()->json(['message' => 'فقط المُنشئ يزيل أعضاء.'], 403);
        }

        if ($user->id === $group->created_by) {
            return response()->json(['message' => 'لا يمكن إزالة منشئ المجموعة.'], 422);
        }

        $group->members()->detach($user->id);

        return response()->json(['message' => 'تمت إزالة العضو.']);
    }
}