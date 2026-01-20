import { Head, Link } from '@inertiajs/react';
import UserLayout from '../../../Layouts/UserLayout';

export default function ClientVehiclesIndex({ auth, vehicles }) {
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
        <UserLayout auth={auth}>
            <Head title="Véhicules disponibles" />
            <div className="py-12">
                <div className="max-w-7xl mx-auto sm:px-6 lg:px-8">
                    <div className="mb-6">
                        <h1 className="text-3xl font-bold text-gray-900 mb-2">
                            Véhicules disponibles
                        </h1>
                        <p className="text-gray-600">
                            Consultez la liste des véhicules disponibles pour réserver
                        </p>
                    </div>

                    {vehicles.data.length === 0 ? (
                        <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                            <div className="p-6 text-center">
                                <p className="text-gray-500 text-lg">
                                    Aucun véhicule disponible pour le moment.
                                </p>
                            </div>
                        </div>
                    ) : (
                        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                            {vehicles.data.map((vehicle) => (
                                <div
                                    key={vehicle.id}
                                    className="bg-white overflow-hidden shadow-sm sm:rounded-lg hover:shadow-md transition-shadow"
                                >
                                    <div className="p-6">
                                        <div className="flex justify-between items-start mb-4">
                                            <div>
                                                <h3 className="text-xl font-semibold text-gray-900">
                                                    {vehicle.brand} {vehicle.model}
                                                </h3>
                                                <p className="text-sm text-gray-500 font-mono mt-1">
                                                    {vehicle.license_plate}
                                                </p>
                                            </div>
                                            <span className={`inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ${getStatusBadgeClass(vehicle.status)}`}>
                                                {getStatusLabel(vehicle.status)}
                                            </span>
                                        </div>

                                        <dl className="grid grid-cols-2 gap-x-4 gap-y-2 text-sm mb-4">
                                            {vehicle.year && (
                                                <div>
                                                    <dt className="text-gray-500">Année</dt>
                                                    <dd className="text-gray-900 font-medium">{vehicle.year}</dd>
                                                </div>
                                            )}
                                            {vehicle.color && (
                                                <div>
                                                    <dt className="text-gray-500">Couleur</dt>
                                                    <dd className="text-gray-900 font-medium">{vehicle.color}</dd>
                                                </div>
                                            )}
                                        </dl>

                                        <Link
                                            href={`/reservations/create?vehicle_id=${vehicle.id}`}
                                            className="block w-full text-center px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700 transition-colors font-medium"
                                        >
                                            Réserver ce véhicule
                                        </Link>
                                    </div>
                                </div>
                            ))}
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
                                        Affichage de <span className="font-medium">{vehicles.from || 0}</span> à{' '}
                                        <span className="font-medium">{vehicles.to || 0}</span> sur{' '}
                                        <span className="font-medium">{vehicles.total || 0}</span> résultats
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
        </UserLayout>
    );
}
