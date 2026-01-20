import { Head, Link, router } from '@inertiajs/react';
import AdminLayout from '../../../Layouts/AdminLayout';
import Button from '../../../Components/Button';

export default function ReservationsShow({ auth, reservation, statuses = [] }) {
    const handleDelete = () => {
        if (confirm(`Êtes-vous sûr de vouloir supprimer cette réservation ?`)) {
            router.delete(`/admin/reservations/${reservation.id}`);
        }
    };

    const handleCancel = () => {
        if (confirm('Êtes-vous sûr de vouloir annuler cette réservation ?')) {
            router.post(`/admin/reservations/${reservation.id}/cancel`);
        }
    };

    const handleConfirm = () => {
        if (confirm('Êtes-vous sûr de vouloir confirmer cette réservation ?')) {
            router.post(`/admin/reservations/${reservation.id}/confirm`);
        }
    };

    const handleComplete = () => {
        if (confirm('Êtes-vous sûr de vouloir finaliser cette réservation ?')) {
            router.post(`/admin/reservations/${reservation.id}/complete`);
        }
    };

    const getStatusBadgeClass = (status) => {
        switch (status) {
            case 'pending':
                return 'bg-yellow-100 text-yellow-800';
            case 'confirmed':
                return 'bg-green-100 text-green-800';
            case 'cancelled':
                return 'bg-red-100 text-red-800';
            case 'completed':
                return 'bg-blue-100 text-blue-800';
            default:
                return 'bg-gray-100 text-gray-800';
        }
    };

    const getStatusLabel = (status) => {
        switch (status) {
            case 'pending':
                return 'En attente';
            case 'confirmed':
                return 'Confirmée';
            case 'cancelled':
                return 'Annulée';
            case 'completed':
                return 'Terminée';
            default:
                return status;
        }
    };

    const canCancel = reservation.status === 'pending' || reservation.status === 'confirmed';
    const canConfirm = reservation.status === 'pending';
    const canComplete = reservation.status === 'confirmed';

    return (
        <AdminLayout auth={auth}>
            <Head title={`Réservation #${reservation.id}`} />
            <div className="py-12">
                <div className="max-w-4xl mx-auto sm:px-6 lg:px-8">
                    <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div className="p-6">
                            <div className="flex justify-between items-center mb-6">
                                <h1 className="text-3xl font-bold text-gray-900">
                                    Détails de la réservation
                                </h1>
                                <div className="flex gap-2">
                                    <Link
                                        href="/admin/reservations"
                                        className="text-gray-600 hover:text-gray-900"
                                    >
                                        ← Retour
                                    </Link>
                                    <Link
                                        href={`/admin/reservations/${reservation.id}/edit`}
                                        className="px-4 py-2 bg-yellow-600 text-white rounded-md hover:bg-yellow-700"
                                    >
                                        Modifier
                                    </Link>
                                    <Button
                                        onClick={handleDelete}
                                        variant="danger"
                                    >
                                        Supprimer
                                    </Button>
                                </div>
                            </div>

                            <div className="space-y-6">
                                {/* Informations générales */}
                                <div>
                                    <h2 className="text-xl font-semibold text-gray-900 mb-4">
                                        Informations générales
                                    </h2>
                                    <dl className="grid grid-cols-1 gap-x-4 gap-y-6 sm:grid-cols-2">
                                        <div>
                                            <dt className="text-sm font-medium text-gray-500">ID</dt>
                                            <dd className="mt-1 text-sm text-gray-900">{reservation.id}</dd>
                                        </div>
                                        <div>
                                            <dt className="text-sm font-medium text-gray-500">Utilisateur</dt>
                                            <dd className="mt-1 text-sm text-gray-900">
                                                {reservation.user ? (
                                                    <>
                                                        {reservation.user.email}
                                                        {reservation.user.first_name || reservation.user.last_name ? (
                                                            <span className="text-gray-500 ml-1">
                                                                ({reservation.user.first_name || ''} {reservation.user.last_name || ''})
                                                            </span>
                                                        ) : null}
                                                    </>
                                                ) : (
                                                    `User #${reservation.user_id}`
                                                )}
                                            </dd>
                                        </div>
                                        <div>
                                            <dt className="text-sm font-medium text-gray-500">Véhicule</dt>
                                            <dd className="mt-1 text-sm text-gray-900">
                                                {reservation.vehicle ? (
                                                    <>
                                                        {reservation.vehicle.brand} {reservation.vehicle.model}
                                                        <span className="text-gray-500 ml-1 font-mono">
                                                            ({reservation.vehicle.license_plate})
                                                        </span>
                                                    </>
                                                ) : (
                                                    `Vehicle #${reservation.vehicle_id}`
                                                )}
                                            </dd>
                                        </div>
                                        <div>
                                            <dt className="text-sm font-medium text-gray-500">Statut</dt>
                                            <dd className="mt-1 text-sm">
                                                <span className={`inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ${getStatusBadgeClass(reservation.status)}`}>
                                                    {getStatusLabel(reservation.status)}
                                                </span>
                                            </dd>
                                        </div>
                                        <div>
                                            <dt className="text-sm font-medium text-gray-500">Date de début</dt>
                                            <dd className="mt-1 text-sm text-gray-900">
                                                {reservation.start_date
                                                    ? new Date(reservation.start_date).toLocaleString('fr-FR')
                                                    : '-'}
                                            </dd>
                                        </div>
                                        <div>
                                            <dt className="text-sm font-medium text-gray-500">Date de fin</dt>
                                            <dd className="mt-1 text-sm text-gray-900">
                                                {reservation.end_date
                                                    ? new Date(reservation.end_date).toLocaleString('fr-FR')
                                                    : '-'}
                                            </dd>
                                        </div>
                                        {reservation.purpose && (
                                            <div className="sm:col-span-2">
                                                <dt className="text-sm font-medium text-gray-500">Motif</dt>
                                                <dd className="mt-1 text-sm text-gray-900">{reservation.purpose}</dd>
                                            </div>
                                        )}
                                        <div>
                                            <dt className="text-sm font-medium text-gray-500">Créé le</dt>
                                            <dd className="mt-1 text-sm text-gray-900">
                                                {reservation.created_at
                                                    ? new Date(reservation.created_at).toLocaleString('fr-FR')
                                                    : '-'}
                                            </dd>
                                        </div>
                                        <div>
                                            <dt className="text-sm font-medium text-gray-500">Modifié le</dt>
                                            <dd className="mt-1 text-sm text-gray-900">
                                                {reservation.updated_at
                                                    ? new Date(reservation.updated_at).toLocaleString('fr-FR')
                                                    : '-'}
                                            </dd>
                                        </div>
                                    </dl>
                                </div>

                                {/* Actions sur le statut */}
                                <div>
                                    <h2 className="text-xl font-semibold text-gray-900 mb-4">
                                        Actions
                                    </h2>
                                    <div className="flex gap-2">
                                        {canConfirm && (
                                            <Button
                                                onClick={handleConfirm}
                                                variant="success"
                                                size="sm"
                                            >
                                                Confirmer
                                            </Button>
                                        )}
                                        {canCancel && (
                                            <Button
                                                onClick={handleCancel}
                                                variant="danger"
                                                size="sm"
                                            >
                                                Annuler
                                            </Button>
                                        )}
                                        {canComplete && (
                                            <Button
                                                onClick={handleComplete}
                                                variant="info"
                                                size="sm"
                                            >
                                                Finaliser
                                            </Button>
                                        )}
                                        {!canConfirm && !canCancel && !canComplete && (
                                            <p className="text-sm text-gray-500">
                                                Aucune action disponible pour ce statut.
                                            </p>
                                        )}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </AdminLayout>
    );
}
