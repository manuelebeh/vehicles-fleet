import { Head, Link } from '@inertiajs/react';

export default function AppLayout({ children }) {
    return (
        <>
            <Head title="Vehicles Fleet" />
            <div className="min-h-screen bg-gray-100">
                <nav className="bg-white shadow-sm">
                    <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                        <div className="flex justify-between h-16">
                            <div className="flex">
                                <Link
                                    href="/"
                                    className="inline-flex items-center px-1 pt-1 text-sm font-medium leading-5 text-gray-900 focus:outline-none focus:border-indigo-700 transition duration-150 ease-in-out"
                                >
                                    Vehicles Fleet
                                </Link>
                            </div>
                        </div>
                    </div>
                </nav>
                <main>{children}</main>
            </div>
        </>
    );
}
