<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class MemberPortalController extends Controller
{
    public function contributions()
    {
        $member = auth()->user()->member;

        if (!$member) {
            return view('member-portal.no-member');
        }

        $contributions = $member->contributions()
            ->with('meeting')
            ->orderByDesc('created_at')
            ->get();

        return view('member-portal.contributions', compact('member', 'contributions'));
    }

    public function loans()
    {
        $member = auth()->user()->member;

        if (!$member) {
            return view('member-portal.no-member');
        }

        $loans = $member->loans()
            ->with(['payments', 'meeting'])
            ->orderByDesc('created_at')
            ->get();

        return view('member-portal.loans', compact('member', 'loans'));
    }
}
