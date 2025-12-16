@extends('layouts.app')

@section('title', 'Mes Heures Supplémentaires')

@section('content')
    <div class="mb-3">
        <a href="{{ route('overtimes.create') }}" class="btn btn-success">➕ Ajouter des heures</a>
    </div>

    <div class="stats">
        <div class="stat-card">
            <h3>Total des Heures</h3>
            <div class="value">{{ number_format($totalHours, 2) }}h</div>
        </div>
        <div class="stat-card" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);">
            <h3>Montant Total</h3>
            <div class="value">{{ number_format($totalAmount, 2) }}€</div>
        </div>
    </div>

    <div class="card">
        <h2 style="margin-bottom: 20px;">Liste des Heures Supplémentaires</h2>

        @if($overtimes->isEmpty())
            <p style="text-align: center; padding: 40px; color: #7f8c8d;">
                Aucune heure supplémentaire enregistrée. Cliquez sur "Ajouter des heures" pour commencer.
            </p>
        @else
            <table>
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Heures</th>
                        <th>Type</th>
                        <th>Taux horaire</th>
                        <th>Montant</th>
                        <th>Description</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($overtimes as $overtime)
                        <tr>
                            <td>{{ $overtime->date->format('d/m/Y') }}</td>
                            <td>{{ number_format($overtime->hours, 2) }}h</td>
                            <td>
                                @if($overtime->type === 'majored')
                                    <span style="background-color: #e74c3c; color: white; padding: 4px 8px; border-radius: 4px; font-size: 12px;">Majorées</span>
                                @else
                                    <span style="background-color: #3498db; color: white; padding: 4px 8px; border-radius: 4px; font-size: 12px;">Normales</span>
                                @endif
                            </td>
                            <td>{{ number_format($overtime->rate, 2) }}€</td>
                            <td><strong>{{ number_format($overtime->total_amount, 2) }}€</strong></td>
                            <td>{{ Str::limit($overtime->description, 50) }}</td>
                            <td>
                                <div class="actions">
                                    <a href="{{ route('overtimes.edit', $overtime) }}" class="btn btn-warning" style="padding: 6px 12px; font-size: 14px;">✏️ Modifier</a>
                                    <form action="{{ route('overtimes.destroy', $overtime) }}" method="POST" style="display: inline;" onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer cette entrée ?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-danger" style="padding: 6px 12px; font-size: 14px;">🗑️ Supprimer</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>
@endsection
