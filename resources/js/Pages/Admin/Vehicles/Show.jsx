import { Head, Link, router, useForm } from '@inertiajs/react';
import AppLayout from '../../../Layouts/AppLayout';

export default function VehiclesShow({ auth, vehicle, statuses = [] }) {
    const { data, setData, post, processing, errors } = useForm({
        status: vehicle.status || 'available',
    });

    const handleDelete = () => {
        if (confirm(`Êtes-vous sûr de vouloir supprimer le véhicule ${vehicle.license_plate} ?`)) {
            router.delete(`/admin/vehicles/${vehicle.id}`);
        }
    };

    const handleStatusUpdate = (e) => {
        e.preventDefault();
        post(`/admin/vehicles/${vehicle.id}/status`);
    };

    const getStatusBadgeClass = (status) => {
        switch (status) {
            case 'available':
                return 'bg-green-100 text-green-800';
            case 'maintenance':
                return 'bg-yellow-100 text-yellow-800';
            case 'out_of_service':
                return 'bg-red-100 text-red-800';
            default:
                return 'bg-gray-100 text-gray-800';
        }
    };

    const getStatusLabel = (status) => {
        switch (status) {
            case 'available':
                return 'Disponible';
            case 'maintenance':
                return 'En maintenance';
            case 'out_of_service':
                return 'Hors service';
            default:
                return status;
        }
    };

    return (
        <AppLayout auth={auth}>
            <Head title={`Véhicule: ${vehicle.license_plate}`} />
            <div className="py-12">
                <div className="max-w-4xl mx-auto sm:px-6 lg:px-8">
                    <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div className="p-6">
                            <div className="flex justify-between items-center mb-6">
                                <h1 className="text-3xl font-bold text-gray-900">
                                    Détails du véhicule
                                </h1>
                                <div className="flex gap-2">
                                    <Link
                                        href="/admin/vehicles"
                                        className="text-gray-600 hover:text-gray-900"
                                    >
                                        ← Retour
                                    </Link>
                                    <Link
                                        href={`/admin/vehicles/${vehicle.id}/edit`}
                                        className="px-4 py-2 bg-yellow-600 text-white rounded-md hover:bg-yellow-700"
                                    >
                                        Modifier
                                    </Link>
                                    <button
                                        onClick={handleDelete}
                                        className="px-4 py-2 bg-red-600 text-white rounded-md hover:bg-red-700"
                                    >
                                        Supprimer
                                    </button>
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
                                            <dd className="mt-1 text-sm text-gray-900">{vehicle.id}</dd>
                                        </div>
                                        <div>
                                            <dt className="text-sm font-medium text-gray-500">Marque</dt>
                                            <dd className="mt-1 text-sm text-gray-900">{vehicle.brand}</dd>
                                        </div>
                                        <div>
                                            <dt className="text-sm font-medium text-gray-500">Modèle</dt>
                                            <dd className="mt-1 text-sm text-gray-900">{vehicle.model}</dd>
                                        </div>
                                        <div>
                                            <dt className="text-sm font-medium text-gray-500">Plaque d'immatriculation</dt>
                                            <dd className="mt-1 text-sm text-gray-900 font-mono">{vehicle.license_plate}</dd>
                                        </div>
                                        <div>
                                            <dt className="text-sm font-medium text-gray-500">Année</dt>
                                            <dd className="mt-1 text-sm text-gray-900">
                                                {vehicle.year || '-'}
                                            </dd>
                                        </div>
                                        <div>
                                            <dt className="text-sm font-medium text-gray-500">Couleur</dt>
                                            <dd className="mt-1 text-sm text-gray-900">
                                                {vehicle.color || '-'}
                                            </dd>
                                        </div>
                                        <div>
                                            <dt className="text-sm font-medium text-gray-500">Statut</dt>
                                            <dd className="mt-1 text-sm">
                                                <span className={`inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ${getStatusBadgeClass(vehicle.status)}`}>
                                                    {getStatusLabel(vehicle.status)}
                                                </span>
                                            </dd>
                                        </div>
                                        <div>
                                            <dt className="text-sm font-medium text-gray-500">Créé le</dt>
                                            <dd className="mt-1 text-sm text-gray-900">
                                                {vehicle.created_at
                                                    ? new Date(vehicle.created_at).toLocaleString('fr-FR')
                                                    : '-'}
                                            </dd>
                                        </div>
                                        <div>
                                            <dt className="text-sm font-medium text-gray-500">Modifié le</dt>
                                            <dd className="mt-1 text-sm text-gray-900">
                                                {vehicle.updated_at
                                                    ? new Date(vehicle.updated_at).toLocaleString('fr-FR')
                                                    : '-'}
                                            </dd>
                                        </div>
                                    </dl>
                                </div>

                                {/* Changement de statut */}
                                <div>
                                    <h2 className="text-xl font-semibold text-gray-900 mb-4">
                                        Changer le statut
                                    </h2>
                                    <form onSubmit={handleStatusUpdate} className="flex items-end gap-4">
                                        <div className="flex-1">
                                            <label htmlFor="status" className="block text-sm font-medium text-gray-700 mb-2">
                                                Nouveau statut
                                            </label>
                                            <select
                                                id="status"
                                                value={data.status}
                                                onChange={(e) => setData('status', e.target.value)}
                                                className={`block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 ${
                                                    errors.status ? 'border-red-500' : ''
                                                }`}
                                            >
                                                {statuses.map((status) => (
                                                    <option key={status} value={status}>
                                                        {getStatusLabel(status)}
                                                    </option>
                                                ))}
                                            </select>
                                            {errors.status && (
                                                <p className="mt-1 text-sm text-red-600">{errors.status}</p>
                                            )}
                                        </div>
                                        <button
                                            type="submit"
                                            disabled={processing || data.status === vehicle.status}
                                            className="px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 disabled:opacity-50"
                                        >
                                            {processing ? 'Mise à jour...' : 'Mettre à jour'}
                                        </button>
                                    </form>
                                </div>

                                {/* Réservations */}
                                {vehicle.reservations_count !== undefined && (
                                    <div>
                                        <h2 className="text-xl font-semibold text-gray-900 mb-4">
                                            Réservations
                                        </h2>
                                        <p className="text-gray-700">
                                            Nombre de réservations :{' '}
                                            <span className="font-semibold">
                                                {vehicle.reservations_count || 0}
                                            </span>
                                        </p>
                                    </div>
                                )}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}
