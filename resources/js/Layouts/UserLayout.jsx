import { Head, Link } from '@inertiajs/react';
import FlashMessages from '../Components/FlashMessages';

export default function UserLayout({ children, auth }) {
    return (
        <>
            <Head title="Vehicles Fleet" />
            <div className="min-h-screen bg-gray-100">
                <nav className="bg-white shadow-sm">
                    <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                        <div className="flex justify-between h-16">
                            <div className="flex items-center space-x-4">
                                <Link
                                    href="/"
                                    className="inline-flex items-center px-1 pt-1 text-sm font-medium leading-5 text-gray-900 focus:outline-none focus:border-indigo-700 transition duration-150 ease-in-out"
                                >
                                    Vehicles Fleet
                                </Link>
                                {auth?.user && (
                                    <>
                                        <Link
                                            href="/vehicles"
                                            className="inline-flex items-center px-3 py-2 text-sm font-medium text-gray-700 hover:text-gray-900 focus:outline-none focus:text-gray-900 transition duration-150 ease-in-out"
                                        >
                                            Véhicules
                                        </Link>
                                        <Link
                                            href="/reservations"
                                            className="inline-flex items-center px-3 py-2 text-sm font-medium text-gray-700 hover:text-gray-900 focus:outline-none focus:text-gray-900 transition duration-150 ease-in-out"
                                        >
                                            Mes réservations
                                        </Link>
                                        {auth.user.roles?.includes('admin') && (
                                            <Link
                                                href="/admin"
                                                className="inline-flex items-center px-3 py-2 text-sm font-medium text-gray-700 hover:text-gray-900 focus:outline-none focus:text-gray-900 transition duration-150 ease-in-out"
                                            >
                                                Administration
                                            </Link>
                                        )}
                                    </>
                                )}
                            </div>
                            <div className="flex items-center space-x-4">
                                {auth?.user ? (
                                    <>
                                        <span className="text-sm text-gray-700">
                                            {auth.user.email}
                                        </span>
                                        <Link
                                            href="/logout"
                                            method="post"
                                            className="inline-flex items-center px-3 py-2 text-sm font-medium text-gray-700 hover:text-gray-900 focus:outline-none focus:text-gray-900 transition duration-150 ease-in-out"
                                        >
                                            Déconnexion
                                        </Link>
                                    </>
                                ) : (
                                    <Link
                                        href="/login"
                                        className="inline-flex items-center px-3 py-2 text-sm font-medium text-gray-700 hover:text-gray-900 focus:outline-none focus:text-gray-900 transition duration-150 ease-in-out"
                                    >
                                        Connexion
                                    </Link>
                                )}
                            </div>
                        </div>
                    </div>
                </nav>
                <main>
                    <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4">
                        <FlashMessages />
                    </div>
                    {children}
                </main>
            </div>
        </>
    );
}
