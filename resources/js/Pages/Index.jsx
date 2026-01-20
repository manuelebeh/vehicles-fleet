import { Head, Link } from '@inertiajs/react';
import AppLayout from '../Layouts/AppLayout';

export default function Index({ auth }) {
    return (
        <AppLayout auth={auth}>
            <Head title="Accueil" />
            <div className="py-12">
                <div className="max-w-7xl mx-auto sm:px-6 lg:px-8">
                    <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div className="p-6 text-gray-900">
                            <h1 className="text-3xl font-bold mb-4">
                                Bienvenue sur Vehicles Fleet
                            </h1>
                            <p className="text-lg text-gray-600 mb-6">
                                Système de gestion de flotte de véhicules
                            </p>
                            
                            {auth?.user ? (
                                <div className="mt-4">
                                    <p className="text-gray-700">
                                        Connecté en tant que : <strong>{auth.user.email}</strong>
                                    </p>
                                    <div className="mt-4">
                                        <Link
                                            href="/logout"
                                            method="post"
                                            className="inline-flex items-center px-4 py-2 bg-red-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-red-700 focus:bg-red-700 active:bg-red-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150"
                                        >
                                            Déconnexion
                                        </Link>
                                    </div>
                                </div>
                            ) : (
                                <div className="mt-4">
                                    <Link
                                        href="/login"
                                        className="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 focus:bg-indigo-700 active:bg-indigo-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150"
                                    >
                                        Se connecter
                                    </Link>
                                </div>
                            )}
                        </div>
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}
