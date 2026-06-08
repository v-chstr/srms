<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserSearchController extends Controller
{
    /**
     * Search active users by name for the author-input autocomplete.
     * Returns a capped list of matching users — does NOT restrict who can be added as an author.
     *
     * Optional: ?role=adviser to restrict to adviser-capable users.
     */
    public function search(Request $request): JsonResponse
    {
        $q    = trim($request->query('q', ''));
        $role = $request->query('role');

        if (mb_strlen($q) < 2) {
            return response()->json([]);
        }

        $query = User::where('status', 'active')
            ->where(fn ($query) => $query
                ->where('first_name', 'like', "%{$q}%")
                ->orWhere('last_name', 'like', "%{$q}%")
                ->orWhereRaw("CONCAT(first_name, ' ', last_name) LIKE ?", ["%{$q}%"])
                ->orWhereRaw("CONCAT(last_name, ', ', first_name) LIKE ?", ["%{$q}%"])
            );

        // Optionally restrict to adviser-capable users
        if ($role === 'adviser') {
            $query->where(fn ($q) => $q
                ->where('role', 'adviser')
                ->orWhere(fn ($q2) => $q2->where('role', 'admin')->where('is_adviser', true))
            );
        }

        $users = $query
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->limit(8)
            ->get(['id', 'first_name', 'last_name'])
            ->map(fn ($u) => [
                'id'         => $u->id,
                'first_name' => $u->first_name,
                'last_name'  => $u->last_name,
                'display'    => $u->first_name . ' ' . $u->last_name,
            ]);

        return response()->json($users);
    }
}
