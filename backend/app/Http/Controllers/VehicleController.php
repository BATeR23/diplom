<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreVehicleRequest;
use App\Http\Requests\UpdateVehicleRequest;
use App\Models\Vehicle;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class VehicleController extends Controller
{
    public function index(Request $request)
    {
        return $request->user()->vehicles()->latest()->get();
    }

    public function store(StoreVehicleRequest $request)
    {
        $data = $request->validated();

        // Нормализуем boolean значения (FormData может передавать их как строки '1', '0', 'true', 'false')
        $allowsPets = false;
        if (isset($data['allows_pets'])) {
            $allowsPets = filter_var($data['allows_pets'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
            if ($allowsPets === null) {
                $allowsPets = in_array(strtolower((string)$data['allows_pets']), ['1', 'true', 'yes', 'on'], true);
            }
        }
        
        $allowsSmoking = false;
        if (isset($data['allows_smoking'])) {
            $allowsSmoking = filter_var($data['allows_smoking'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
            if ($allowsSmoking === null) {
                $allowsSmoking = in_array(strtolower((string)$data['allows_smoking']), ['1', 'true', 'yes', 'on'], true);
            }
        }

        // Сохраняем документы
        $ownershipPath = $request->file('ownership_document')->store('vehicle-documents', 'public');
        $licensePath = $request->file('license_document')->store('vehicle-documents', 'public');

        $vehicle = $request->user()->vehicles()->create([
            'make' => $data['make'],
            'model' => $data['model'],
            'year' => $data['year'] ?? null,
            'seats' => $data['seats'],
            'color' => $data['color'] ?? null,
            'plate_number' => $data['plate_number'] ?? null,
            'comfort_class' => $data['comfort_class'],
            'features' => $data['features'] ?? null,
            'allows_pets' => $allowsPets,
            'allows_smoking' => $allowsSmoking,
            'ownership_document_path' => $ownershipPath,
            'license_document_path' => $licensePath,
            'verification_status' => 'pending',
        ]);

        return response()->json($vehicle, 201);
    }

    public function show(Request $request, Vehicle $vehicle)
    {
        $this->authorizeVehicle($request, $vehicle);

        return $vehicle;
    }

    public function update(UpdateVehicleRequest $request, Vehicle $vehicle)
    {
        $this->authorizeVehicle($request, $vehicle);

        $data = $request->validated();

        $vehicle->update($data);

        return $vehicle->refresh();
    }

    public function destroy(Request $request, Vehicle $vehicle)
    {
        $this->authorizeVehicle($request, $vehicle);
        $vehicle->delete();

        return response()->json([], 204);
    }

    protected function authorizeVehicle(Request $request, Vehicle $vehicle): void
    {
        abort_unless($vehicle->user_id === $request->user()->id, 403, 'Недостаточно прав для доступа к этому автомобилю.');
    }
}










