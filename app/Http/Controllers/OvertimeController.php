<?php

namespace App\Http\Controllers;

use App\Models\Overtime;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class OvertimeController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $overtimes = Overtime::where('user_id', Auth::id())
            ->orderBy('date', 'desc')
            ->get();

        $totalHours = $overtimes->sum('hours');
        $totalAmount = $overtimes->sum(function ($overtime) {
            return $overtime->hours * $overtime->rate;
        });

        return view('overtimes.index', compact('overtimes', 'totalHours', 'totalAmount'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('overtimes.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'date' => 'required|date',
            'hours' => 'required|numeric|min:0|max:24',
            'description' => 'nullable|string|max:1000',
            'type' => 'required|in:normal,majored',
            'rate' => 'required|numeric|min:0',
        ]);

        $validated['user_id'] = Auth::id();

        Overtime::create($validated);

        return redirect()->route('overtimes.index')
            ->with('success', 'Heures supplémentaires ajoutées avec succès.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Overtime $overtime)
    {
        $this->authorize('view', $overtime);
        return view('overtimes.show', compact('overtime'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Overtime $overtime)
    {
        $this->authorize('update', $overtime);
        return view('overtimes.edit', compact('overtime'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Overtime $overtime)
    {
        $this->authorize('update', $overtime);

        $validated = $request->validate([
            'date' => 'required|date',
            'hours' => 'required|numeric|min:0|max:24',
            'description' => 'nullable|string|max:1000',
            'type' => 'required|in:normal,majored',
            'rate' => 'required|numeric|min:0',
        ]);

        $overtime->update($validated);

        return redirect()->route('overtimes.index')
            ->with('success', 'Heures supplémentaires modifiées avec succès.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Overtime $overtime)
    {
        $this->authorize('delete', $overtime);
        
        $overtime->delete();

        return redirect()->route('overtimes.index')
            ->with('success', 'Heures supplémentaires supprimées avec succès.');
    }
}
