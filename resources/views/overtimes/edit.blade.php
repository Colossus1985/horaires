@extends('layouts.app')

@section('title', 'Modifier des Heures Supplémentaires')

@section('content')
    <div class="card">
        <h2 style="margin-bottom: 25px;">Modifier des Heures Supplémentaires</h2>

        <form action="{{ route('overtimes.update', $overtime) }}" method="POST">
            @csrf
            @method('PUT')

            <div class="form-group">
                <label for="date">Date *</label>
                <input type="date" id="date" name="date" class="form-control" value="{{ old('date', $overtime->date->format('Y-m-d')) }}" required>
                @error('date')
                    <div class="error">{{ $message }}</div>
                @enderror
            </div>

            <div class="form-group">
                <label for="hours">Nombre d'heures *</label>
                <input type="number" id="hours" name="hours" class="form-control" step="0.01" min="0" max="24" value="{{ old('hours', $overtime->hours) }}" required>
                @error('hours')
                    <div class="error">{{ $message }}</div>
                @enderror
            </div>

            <div class="form-group">
                <label for="type">Type *</label>
                <select id="type" name="type" class="form-control" required>
                    <option value="normal" {{ old('type', $overtime->type) == 'normal' ? 'selected' : '' }}>Heures normales</option>
                    <option value="majored" {{ old('type', $overtime->type) == 'majored' ? 'selected' : '' }}>Heures majorées</option>
                </select>
                @error('type')
                    <div class="error">{{ $message }}</div>
                @enderror
            </div>

            <div class="form-group">
                <label for="rate">Taux horaire (€) *</label>
                <input type="number" id="rate" name="rate" class="form-control" step="0.01" min="0" value="{{ old('rate', $overtime->rate) }}" required>
                @error('rate')
                    <div class="error">{{ $message }}</div>
                @enderror
            </div>

            <div class="form-group">
                <label for="description">Description</label>
                <textarea id="description" name="description" class="form-control" rows="4" placeholder="Optionnel : Ajouter une description...">{{ old('description', $overtime->description) }}</textarea>
                @error('description')
                    <div class="error">{{ $message }}</div>
                @enderror
            </div>

            <div style="display: flex; gap: 10px;">
                <button type="submit" class="btn btn-primary">💾 Mettre à jour</button>
                <a href="{{ route('overtimes.index') }}" class="btn" style="background-color: #95a5a6; color: white;">❌ Annuler</a>
            </div>
        </form>
    </div>
@endsection
