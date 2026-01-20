import { Head, Link, router } from '@inertiajs/react';
import AppLayout from '../../../Layouts/AppLayout';

export default function VehiclesIndex({ auth, vehicles }) {
    const handleDelete = (vehicleId, licensePlate) => {
        if (confirm(`Êtes-vous sûr de vouloir supprimer le véhicule ${licensePlate} ?`)) {
            router.delete(`/admin/vehicles/${vehicleId}`, {
                preserveScroll: true,
            });
        }
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
            <Head title="Gestion des véhicules" />
            <div className="py-12">
                <div className="max-w-7xl mx-auto sm:px-6 lg:px-8">
                    <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div className="p-6">
                            <div className="flex justify-between items-center mb-6">
                                <h1 className="text-3xl font-bold text-gray-900">
                                    Gestion des véhicules
                                </h1>
                                <Link
                                    href="/admin/vehicles/create"
                                    className="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 focus:bg-indigo-700 active:bg-indigo-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150"
                                >
                                    Nouveau véhicule
                                </Link>
                            </div>

                            <div className="overflow-x-auto">
                                <table className="min-w-full divide-y divide-gray-200">
                                    <thead className="bg-gray-50">
                                        <tr>
                                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                ID
                                            </th>
                                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                Marque / Modèle
                                            </th>
                                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                Plaque
                                            </th>
                                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                Année
                                            </th>
                                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                Couleur
                                            </th>
                                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                Statut
                                            </th>
                                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                Créé le
                                            </th>
                                            <th className="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                Actions
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody className="bg-white divide-y divide-gray-200">
                                        {vehicles.data.map((vehicle) => (
                                            <tr key={vehicle.id} className="hover:bg-gray-50">
                                                <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                    {vehicle.id}
                                                </td>
                                                <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                    {vehicle.brand} {vehicle.model}
                                                </td>
                                                <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900 font-mono">
                                                    {vehicle.license_plate}
                                                </td>
                                                <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                    {vehicle.year || '-'}
                                                </td>
                                                <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                    {vehicle.color || '-'}
                                                </td>
                                                <td className="px-6 py-4 whitespace-nowrap text-sm">
                                                    <span className={`inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ${getStatusBadgeClass(vehicle.status)}`}>
                                                        {getStatusLabel(vehicle.status)}
                                                    </span>
                                                </td>
                                                <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                    {vehicle.created_at
                                                        ? new Date(vehicle.created_at).toLocaleDateString('fr-FR')
                                                        : '-'}
                                                </td>
                                                <td className="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                                    <div className="flex justify-end gap-2">
                                                        <Link
                                                            href={`/admin/vehicles/${vehicle.id}`}
                                                            className="text-indigo-600 hover:text-indigo-900"
                                                        >
                                                            Voir
                                                        </Link>
                                                        <Link
                                                            href={`/admin/vehicles/${vehicle.id}/edit`}
                                                            className="text-yellow-600 hover:text-yellow-900"
                                                        >
                                                            Modifier
                                                        </Link>
                                                        <button
                                                            onClick={() => handleDelete(vehicle.id, vehicle.license_plate)}
                                                            className="text-red-600 hover:text-red-900"
                                                        >
                                                            Supprimer
                                                        </button>
                                                    </div>
                                                </td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>

                            {vehicles.data.length === 0 && (
                                <div className="text-center py-12">
                                    <p className="text-gray-500">Aucun véhicule trouvé.</p>
                                </div>
                            )}

                            {/* Pagination */}
                            {vehicles.links && vehicles.links.length > 3 && (
                                <div className="mt-6 flex items-center justify-between">
                                    <div className="flex-1 flex justify-between sm:hidden">
                                        {vehicles.links[0].url && (
                                            <Link
                                                href={vehicles.links[0].url}
                                                className="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50"
                                            >
                                                Précédent
                                            </Link>
                                        )}
                                        {vehicles.links[vehicles.links.length - 1].url && (
                                            <Link
                                                href={vehicles.links[vehicles.links.length - 1].url}
                                                className="ml-3 relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50"
                                            >
                                                Suivant
                                            </Link>
                                        )}
                                    </div>
                                    <div className="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
                                        <div>
                                            <p className="text-sm text-gray-700">
                                                Affichage de <span className="font-medium">{vehicles.from}</span> à{' '}
                                                <span className="font-medium">{vehicles.to}</span> sur{' '}
                                                <span className="font-medium">{vehicles.total}</span> résultats
                                            </p>
                                        </div>
                                        <div>
                                            <nav className="relative z-0 inline-flex rounded-md shadow-sm -space-x-px" aria-label="Pagination">
                                                {vehicles.links.map((link, index) => (
                                                    <Link
                                                        key={index}
                                                        href={link.url || '#'}
                                                        className={`relative inline-flex items-center px-4 py-2 border text-sm font-medium ${
                                                            link.active
                                                                ? 'z-10 bg-indigo-50 border-indigo-500 text-indigo-600'
                                                                : 'bg-white border-gray-300 text-gray-500 hover:bg-gray-50'
                                                        } ${!link.url ? 'cursor-not-allowed opacity-50' : ''}`}
                                                        dangerouslySetInnerHTML={{ __html: link.label }}
                                                    />
                                                ))}
                                            </nav>
                                        </div>
                                    </div>
                                </div>
                            )}
                        </div>
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}
