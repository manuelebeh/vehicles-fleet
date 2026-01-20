import AppLayout from '../Layouts/AppLayout';

export default function Welcome() {
    return (
        <AppLayout>
            <div className="py-12">
                <div className="max-w-7xl mx-auto sm:px-6 lg:px-8">
                    <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div className="p-6 text-gray-900">
                            <h1 className="text-3xl font-bold mb-4">
                                Welcome to Inertia.js with React!
                            </h1>
                            <p className="text-lg text-gray-600">
                                Your Laravel application is now configured with Inertia.js and React.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}
