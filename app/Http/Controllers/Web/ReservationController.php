<?php

namespace App\Http\Controllers\Web;

use App\Enums\ReservationStatus;
use App\Exceptions\InvalidStatusTransitionException;
use App\Exceptions\ReservationConflictException;
use App\Exceptions\VehicleNotAvailableException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Reservation\ReservationRequest;
use App\Models\Reservation;
use App\Models\User;
use App\Models\Vehicle;
use App\Services\ReservationService;
use App\Services\UserService;
use App\Services\VehicleService;
use App\Traits\HandlesPagination;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class ReservationController extends Controller
{
    use HandlesPagination;

    public function __construct(
        protected ReservationService $reservationService,
        protected UserService $userService,
        protected VehicleService $vehicleService
    ) {
    }

    public function index(Request $request): InertiaResponse
    {
        $perPage = $this->getPerPage($request);
        $reservations = $this->reservationService->getAll($perPage);

        return Inertia::render('Admin/Reservations/Index', [
            'reservations' => $reservations,
        ]);
    }

    public function create(): InertiaResponse
    {
        $users = $this->userService->getAllWithoutPagination();
        $vehicles = $this->vehicleService->getAvailable();
        $statuses = ReservationStatus::all();

        return Inertia::render('Admin/Reservations/Create', [
            'users' => $users,
            'vehicles' => $vehicles,
            'statuses' => $statuses,
        ]);
    }

    public function store(ReservationRequest $request): RedirectResponse
    {
        try {
            $reservation = $this->reservationService->create($request->validated());

            Log::info('Reservation created', [
                'reservation_id' => $reservation->id,
                'user_id' => $reservation->user_id,
                'vehicle_id' => $reservation->vehicle_id,
                'start_date' => $reservation->start_date,
                'end_date' => $reservation->end_date,
                'created_by' => auth()->id(),
            ]);

            return redirect()->route('admin.reservations.index')
                ->with('success', 'Réservation créée avec succès.');
        } catch (ReservationConflictException|VehicleNotAvailableException $e) {
            Log::warning('Reservation creation failed', [
                'error' => $e->getMessage(),
                'user_id' => $request->user_id,
                'vehicle_id' => $request->vehicle_id,
                'created_by' => auth()->id(),
            ]);

            return back()->withErrors([
                'error' => $e->getMessage(),
            ])->withInput();
        } catch (\Exception $e) {
            Log::error('Error creating reservation', [
                'error' => $e->getMessage(),
                'data' => $request->validated(),
                'created_by' => auth()->id(),
            ]);

            return back()->withErrors([
                'error' => 'Une erreur est survenue lors de la création de la réservation.',
            ])->withInput();
        }
    }

    public function show(Reservation $reservation): InertiaResponse
    {
        $reservation->load(['user', 'vehicle']);
        $statuses = ReservationStatus::all();

        return Inertia::render('Admin/Reservations/Show', [
            'reservation' => $reservation,
            'statuses' => $statuses,
        ]);
    }

    public function edit(Reservation $reservation): InertiaResponse
    {
        $reservation->load(['user', 'vehicle']);
        $users = $this->userService->getAllWithoutPagination();
        $vehicles = $this->vehicleService->getAllWithoutPagination();
        $statuses = ReservationStatus::all();

        return Inertia::render('Admin/Reservations/Edit', [
            'reservation' => $reservation,
            'users' => $users,
            'vehicles' => $vehicles,
            'statuses' => $statuses,
        ]);
    }

    public function update(ReservationRequest $request, Reservation $reservation): RedirectResponse
    {
        try {
            $this->reservationService->update($reservation, $request->validated());
            $reservation->refresh();
            $reservation->load(['user', 'vehicle']);

            Log::info('Reservation updated', [
                'reservation_id' => $reservation->id,
                'updated_by' => auth()->id(),
            ]);

            return redirect()->route('admin.reservations.show', $reservation)
                ->with('success', 'Réservation mise à jour avec succès.');
        } catch (ReservationConflictException|VehicleNotAvailableException $e) {
            return back()->withErrors([
                'error' => $e->getMessage(),
            ])->withInput();
        } catch (\Exception $e) {
            Log::error('Error updating reservation', [
                'error' => $e->getMessage(),
                'reservation_id' => $reservation->id,
                'updated_by' => auth()->id(),
            ]);

            return back()->withErrors([
                'error' => 'Une erreur est survenue lors de la mise à jour de la réservation.',
            ])->withInput();
        }
    }

    public function destroy(Reservation $reservation): RedirectResponse
    {
        try {
            $reservationId = $reservation->id;
            $this->reservationService->delete($reservation);

            Log::info('Reservation deleted', [
                'reservation_id' => $reservationId,
                'deleted_by' => auth()->id(),
            ]);

            return redirect()->route('admin.reservations.index')
                ->with('success', 'Réservation supprimée avec succès.');
        } catch (\Exception $e) {
            Log::error('Error deleting reservation', [
                'error' => $e->getMessage(),
                'reservation_id' => $reservation->id,
                'deleted_by' => auth()->id(),
            ]);

            return redirect()->route('admin.reservations.index')
                ->with('error', 'Une erreur est survenue lors de la suppression de la réservation.');
        }
    }

    public function cancel(Reservation $reservation): RedirectResponse
    {
        try {
            $this->reservationService->cancel($reservation);

            Log::info('Reservation cancelled', [
                'reservation_id' => $reservation->id,
                'cancelled_by' => auth()->id(),
            ]);

            return back()->with('success', 'Réservation annulée avec succès.');
        } catch (InvalidStatusTransitionException $e) {
            return back()->withErrors([
                'error' => $e->getMessage(),
            ]);
        } catch (\Exception $e) {
            Log::error('Error cancelling reservation', [
                'error' => $e->getMessage(),
                'reservation_id' => $reservation->id,
            ]);

            return back()->withErrors([
                'error' => 'Une erreur est survenue lors de l\'annulation de la réservation.',
            ]);
        }
    }

    public function confirm(Reservation $reservation): RedirectResponse
    {
        try {
            $this->reservationService->confirm($reservation);

            Log::info('Reservation confirmed', [
                'reservation_id' => $reservation->id,
                'confirmed_by' => auth()->id(),
            ]);

            return back()->with('success', 'Réservation confirmée avec succès.');
        } catch (ReservationConflictException|VehicleNotAvailableException $e) {
            return back()->withErrors([
                'error' => $e->getMessage(),
            ]);
        } catch (InvalidStatusTransitionException $e) {
            return back()->withErrors([
                'error' => $e->getMessage(),
            ]);
        } catch (\Exception $e) {
            Log::error('Error confirming reservation', [
                'error' => $e->getMessage(),
                'reservation_id' => $reservation->id,
            ]);

            return back()->withErrors([
                'error' => 'Une erreur est survenue lors de la confirmation de la réservation.',
            ]);
        }
    }

    public function complete(Reservation $reservation): RedirectResponse
    {
        try {
            $this->reservationService->complete($reservation);

            Log::info('Reservation completed', [
                'reservation_id' => $reservation->id,
                'completed_by' => auth()->id(),
            ]);

            return back()->with('success', 'Réservation finalisée avec succès.');
        } catch (InvalidStatusTransitionException $e) {
            return back()->withErrors([
                'error' => $e->getMessage(),
            ]);
        } catch (\Exception $e) {
            Log::error('Error completing reservation', [
                'error' => $e->getMessage(),
                'reservation_id' => $reservation->id,
            ]);

            return back()->withErrors([
                'error' => 'Une erreur est survenue lors de la finalisation de la réservation.',
            ]);
        }
    }
}
