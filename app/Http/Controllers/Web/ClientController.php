<?php

namespace App\Http\Controllers\Web;

use App\Exceptions\ReservationConflictException;
use App\Exceptions\VehicleNotAvailableException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Reservation\ReservationRequest;
use App\Models\Reservation;
use App\Services\ReservationService;
use App\Services\VehicleService;
use App\Traits\HandlesPagination;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class ClientController extends Controller
{
    use HandlesPagination;

    public function __construct(
        protected VehicleService $vehicleService,
        protected ReservationService $reservationService
    ) {
    }

    public function vehicles(Request $request): InertiaResponse
    {
        $perPage = $this->getPerPage($request) ?: 12;
        $vehicles = $this->vehicleService->getAvailable();
        
        // Pagination manuelle pour la collection
        $currentPage = (int) $request->get('page', 1);
        $items = $vehicles->forPage($currentPage, $perPage);
        $total = $vehicles->count();
        
        $paginated = new \Illuminate\Pagination\LengthAwarePaginator(
            $items,
            $total,
            $perPage,
            $currentPage,
            [
                'path' => $request->url(),
                'query' => $request->query(),
            ]
        );

        return Inertia::render('Client/Vehicles/Index', [
            'vehicles' => $paginated,
        ]);
    }

    public function reservations(Request $request): InertiaResponse
    {
        $user = auth()->user();
        $perPage = $this->getPerPage($request);
        $reservations = $this->reservationService->getByUser($user, $perPage);

        return Inertia::render('Client/Reservations/Index', [
            'reservations' => $reservations,
        ]);
    }

    public function createReservation(): InertiaResponse
    {
        $vehicles = $this->vehicleService->getAvailable();

        return Inertia::render('Client/Reservations/Create', [
            'vehicles' => $vehicles,
        ]);
    }

    public function storeReservation(ReservationRequest $request): RedirectResponse
    {
        try {
            $data = $request->validated();
            // Forcer l'utilisateur connecté
            $data['user_id'] = auth()->id();
            
            $reservation = $this->reservationService->create($data);

            Log::info('Reservation created by client', [
                'reservation_id' => $reservation->id,
                'user_id' => $reservation->user_id,
                'vehicle_id' => $reservation->vehicle_id,
                'start_date' => $reservation->start_date,
                'end_date' => $reservation->end_date,
            ]);

            return redirect()->route('client.reservations')
                ->with('success', 'Réservation créée avec succès.');
        } catch (ReservationConflictException|VehicleNotAvailableException $e) {
            Log::warning('Reservation creation failed by client', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id(),
                'vehicle_id' => $request->vehicle_id,
            ]);

            return back()->withErrors([
                'error' => $e->getMessage(),
            ])->withInput();
        } catch (\Exception $e) {
            Log::error('Error creating reservation by client', [
                'error' => $e->getMessage(),
                'data' => $request->validated(),
                'user_id' => auth()->id(),
            ]);

            return back()->withErrors([
                'error' => 'Une erreur est survenue lors de la création de la réservation.',
            ])->withInput();
        }
    }

    public function showReservation(Reservation $reservation): InertiaResponse
    {
        // Vérifier que la réservation appartient à l'utilisateur connecté
        if ($reservation->user_id !== auth()->id()) {
            abort(403, 'Accès non autorisé.');
        }

        $reservation->load(['user', 'vehicle']);

        return Inertia::render('Client/Reservations/Show', [
            'reservation' => $reservation,
        ]);
    }

    public function cancelReservation(Reservation $reservation): RedirectResponse
    {
        // Vérifier que la réservation appartient à l'utilisateur connecté
        if ($reservation->user_id !== auth()->id()) {
            abort(403, 'Accès non autorisé.');
        }

        try {
            $this->reservationService->cancel($reservation);

            Log::info('Reservation cancelled by client', [
                'reservation_id' => $reservation->id,
                'user_id' => auth()->id(),
            ]);

            return back()->with('success', 'Réservation annulée avec succès.');
        } catch (\Exception $e) {
            Log::error('Error cancelling reservation by client', [
                'error' => $e->getMessage(),
                'reservation_id' => $reservation->id,
            ]);

            return back()->withErrors([
                'error' => 'Une erreur est survenue lors de l\'annulation de la réservation.',
            ]);
        }
    }
}
