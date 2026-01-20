import { Head, Link } from '@inertiajs/react';
import AppLayout from '../../Layouts/AppLayout';

export default function AdminIndex({ auth }) {
    return (
        <AppLayout auth={auth}>
            <Head title="Administration" />
            <div className="py-12">
                <div className="max-w-7xl mx-auto sm:px-6 lg:px-8">
                    <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div className="p-6 text-gray-900">
                            <h1 className="text-3xl font-bold mb-4">
                                Administration
                            </h1>
                            <p className="text-lg text-gray-600 mb-6">
                                Panneau d'administration de la flotte de véhicules
                            </p>
                            
                            {auth?.user && (
                                <div className="mt-4">
                                    <p className="text-gray-700 mb-4">
                                        Bienvenue, <strong>{auth.user.email}</strong>
                                    </p>
                                    <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 mt-6">
                                        <div className="p-4 border border-gray-200 rounded-lg">
                                            <h3 className="font-semibold text-lg mb-2">Véhicules</h3>
                                            <p className="text-gray-600 text-sm mb-4">Gérer les véhicules de la flotte</p>
                                            <Link
                                                href="/admin/vehicles"
                                                className="text-indigo-600 hover:text-indigo-800 text-sm font-medium"
                                            >
                                                Voir les véhicules →
                                            </Link>
                                        </div>
                                        <div className="p-4 border border-gray-200 rounded-lg">
                                            <h3 className="font-semibold text-lg mb-2">Réservations</h3>
                                            <p className="text-gray-600 text-sm mb-4">Gérer les réservations</p>
                                            <Link
                                                href="/admin/reservations"
                                                className="text-indigo-600 hover:text-indigo-800 text-sm font-medium"
                                            >
                                                Voir les réservations →
                                            </Link>
                                        </div>
                                        <div className="p-4 border border-gray-200 rounded-lg">
                                            <h3 className="font-semibold text-lg mb-2">Utilisateurs</h3>
                                            <p className="text-gray-600 text-sm mb-4">Gérer les utilisateurs</p>
                                            <Link
                                                href="/admin/users"
                                                className="text-indigo-600 hover:text-indigo-800 text-sm font-medium"
                                            >
                                                Voir les utilisateurs →
                                            </Link>
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
