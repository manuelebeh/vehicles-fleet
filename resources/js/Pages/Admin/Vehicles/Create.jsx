import { Head, Link, useForm } from '@inertiajs/react';
import AppLayout from '../../../Layouts/AppLayout';

export default function VehiclesCreate({ auth, statuses = [] }) {
    const { data, setData, post, processing, errors } = useForm({
        brand: '',
        model: '',
        license_plate: '',
        year: '',
        color: '',
        status: 'available',
    });

    const handleSubmit = (e) => {
        e.preventDefault();
        post('/admin/vehicles');
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
            <Head title="Créer un véhicule" />
            <div className="py-12">
                <div className="max-w-2xl mx-auto sm:px-6 lg:px-8">
                    <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div className="p-6">
                            <div className="flex justify-between items-center mb-6">
                                <h1 className="text-3xl font-bold text-gray-900">
                                    Créer un véhicule
                                </h1>
                                <Link
                                    href="/admin/vehicles"
                                    className="text-gray-600 hover:text-gray-900"
                                >
                                    ← Retour
                                </Link>
                            </div>

                            <form onSubmit={handleSubmit}>
                                <div className="space-y-6">
                                    {/* Brand */}
                                    <div>
                                        <label htmlFor="brand" className="block text-sm font-medium text-gray-700">
                                            Marque <span className="text-red-500">*</span>
                                        </label>
                                        <input
                                            type="text"
                                            id="brand"
                                            value={data.brand}
                                            onChange={(e) => setData('brand', e.target.value)}
                                            className={`mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 ${
                                                errors.brand ? 'border-red-500' : ''
                                            }`}
                                            required
                                            maxLength={100}
                                        />
                                        {errors.brand && (
                                            <p className="mt-1 text-sm text-red-600">{errors.brand}</p>
                                        )}
                                    </div>

                                    {/* Model */}
                                    <div>
                                        <label htmlFor="model" className="block text-sm font-medium text-gray-700">
                                            Modèle <span className="text-red-500">*</span>
                                        </label>
                                        <input
                                            type="text"
                                            id="model"
                                            value={data.model}
                                            onChange={(e) => setData('model', e.target.value)}
                                            className={`mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 ${
                                                errors.model ? 'border-red-500' : ''
                                            }`}
                                            required
                                            maxLength={100}
                                        />
                                        {errors.model && (
                                            <p className="mt-1 text-sm text-red-600">{errors.model}</p>
                                        )}
                                    </div>

                                    {/* License Plate */}
                                    <div>
                                        <label htmlFor="license_plate" className="block text-sm font-medium text-gray-700">
                                            Plaque d'immatriculation <span className="text-red-500">*</span>
                                        </label>
                                        <input
                                            type="text"
                                            id="license_plate"
                                            value={data.license_plate}
                                            onChange={(e) => setData('license_plate', e.target.value.toUpperCase())}
                                            className={`mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 font-mono ${
                                                errors.license_plate ? 'border-red-500' : ''
                                            }`}
                                            required
                                            maxLength={30}
                                        />
                                        {errors.license_plate && (
                                            <p className="mt-1 text-sm text-red-600">{errors.license_plate}</p>
                                        )}
                                    </div>

                                    {/* Year */}
                                    <div>
                                        <label htmlFor="year" className="block text-sm font-medium text-gray-700">
                                            Année
                                        </label>
                                        <input
                                            type="number"
                                            id="year"
                                            value={data.year}
                                            onChange={(e) => setData('year', e.target.value ? parseInt(e.target.value) : '')}
                                            className={`mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 ${
                                                errors.year ? 'border-red-500' : ''
                                            }`}
                                            min="1900"
                                            max={new Date().getFullYear() + 1}
                                        />
                                        {errors.year && (
                                            <p className="mt-1 text-sm text-red-600">{errors.year}</p>
                                        )}
                                    </div>

                                    {/* Color */}
                                    <div>
                                        <label htmlFor="color" className="block text-sm font-medium text-gray-700">
                                            Couleur
                                        </label>
                                        <input
                                            type="text"
                                            id="color"
                                            value={data.color}
                                            onChange={(e) => setData('color', e.target.value)}
                                            className={`mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 ${
                                                errors.color ? 'border-red-500' : ''
                                            }`}
                                            maxLength={50}
                                        />
                                        {errors.color && (
                                            <p className="mt-1 text-sm text-red-600">{errors.color}</p>
                                        )}
                                    </div>

                                    {/* Status */}
                                    <div>
                                        <label htmlFor="status" className="block text-sm font-medium text-gray-700">
                                            Statut
                                        </label>
                                        <select
                                            id="status"
                                            value={data.status}
                                            onChange={(e) => setData('status', e.target.value)}
                                            className={`mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 ${
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

                                    {/* Error général */}
                                    {errors.error && (
                                        <div className="rounded-md bg-red-50 p-4">
                                            <p className="text-sm text-red-800">{errors.error}</p>
                                        </div>
                                    )}

                                    {/* Actions */}
                                    <div className="flex items-center justify-end gap-4">
                                        <Link
                                            href="/admin/vehicles"
                                            className="px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
                                        >
                                            Annuler
                                        </Link>
                                        <button
                                            type="submit"
                                            disabled={processing}
                                            className="px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 disabled:opacity-50"
                                        >
                                            {processing ? 'Création...' : 'Créer'}
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}
