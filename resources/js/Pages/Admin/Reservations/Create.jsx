import { Head, Link, useForm } from '@inertiajs/react';
import AppLayout from '../../../Layouts/AppLayout';

export default function ReservationsCreate({ auth, users = [], vehicles = [], statuses = [] }) {
    const { data, setData, post, processing, errors } = useForm({
        user_id: '',
        vehicle_id: '',
        start_date: '',
        end_date: '',
        purpose: '',
        status: 'pending',
    });

    const handleSubmit = (e) => {
        e.preventDefault();
        post('/admin/reservations');
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

    return (
        <AppLayout auth={auth}>
            <Head title="Créer une réservation" />
            <div className="py-12">
                <div className="max-w-2xl mx-auto sm:px-6 lg:px-8">
                    <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div className="p-6">
                            <div className="flex justify-between items-center mb-6">
                                <h1 className="text-3xl font-bold text-gray-900">
                                    Créer une réservation
                                </h1>
                                <Link
                                    href="/admin/reservations"
                                    className="text-gray-600 hover:text-gray-900"
                                >
                                    ← Retour
                                </Link>
                            </div>

                            <form onSubmit={handleSubmit}>
                                <div className="space-y-6">
                                    {/* User */}
                                    <div>
                                        <label htmlFor="user_id" className="block text-sm font-medium text-gray-700">
                                            Utilisateur <span className="text-red-500">*</span>
                                        </label>
                                        <select
                                            id="user_id"
                                            value={data.user_id}
                                            onChange={(e) => setData('user_id', e.target.value)}
                                            className={`mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 ${
                                                errors.user_id ? 'border-red-500' : ''
                                            }`}
                                            required
                                        >
                                            <option value="">Sélectionner un utilisateur</option>
                                            {users.map((user) => (
                                                <option key={user.id} value={user.id}>
                                                    {user.email} {user.first_name || user.last_name ? `(${user.first_name || ''} ${user.last_name || ''})`.trim() : ''}
                                                </option>
                                            ))}
                                        </select>
                                        {errors.user_id && (
                                            <p className="mt-1 text-sm text-red-600">{errors.user_id}</p>
                                        )}
                                    </div>

                                    {/* Vehicle */}
                                    <div>
                                        <label htmlFor="vehicle_id" className="block text-sm font-medium text-gray-700">
                                            Véhicule <span className="text-red-500">*</span>
                                        </label>
                                        <select
                                            id="vehicle_id"
                                            value={data.vehicle_id}
                                            onChange={(e) => setData('vehicle_id', e.target.value)}
                                            className={`mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 ${
                                                errors.vehicle_id ? 'border-red-500' : ''
                                            }`}
                                            required
                                        >
                                            <option value="">Sélectionner un véhicule</option>
                                            {vehicles.map((vehicle) => (
                                                <option key={vehicle.id} value={vehicle.id}>
                                                    {vehicle.brand} {vehicle.model} - {vehicle.license_plate}
                                                </option>
                                            ))}
                                        </select>
                                        {errors.vehicle_id && (
                                            <p className="mt-1 text-sm text-red-600">{errors.vehicle_id}</p>
                                        )}
                                    </div>

                                    {/* Start Date */}
                                    <div>
                                        <label htmlFor="start_date" className="block text-sm font-medium text-gray-700">
                                            Date et heure de début <span className="text-red-500">*</span>
                                        </label>
                                        <input
                                            type="datetime-local"
                                            id="start_date"
                                            value={data.start_date}
                                            onChange={(e) => setData('start_date', e.target.value)}
                                            className={`mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 ${
                                                errors.start_date ? 'border-red-500' : ''
                                            }`}
                                            required
                                        />
                                        {errors.start_date && (
                                            <p className="mt-1 text-sm text-red-600">{errors.start_date}</p>
                                        )}
                                    </div>

                                    {/* End Date */}
                                    <div>
                                        <label htmlFor="end_date" className="block text-sm font-medium text-gray-700">
                                            Date et heure de fin <span className="text-red-500">*</span>
                                        </label>
                                        <input
                                            type="datetime-local"
                                            id="end_date"
                                            value={data.end_date}
                                            onChange={(e) => setData('end_date', e.target.value)}
                                            className={`mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 ${
                                                errors.end_date ? 'border-red-500' : ''
                                            }`}
                                            required
                                        />
                                        {errors.end_date && (
                                            <p className="mt-1 text-sm text-red-600">{errors.end_date}</p>
                                        )}
                                    </div>

                                    {/* Purpose */}
                                    <div>
                                        <label htmlFor="purpose" className="block text-sm font-medium text-gray-700">
                                            Motif
                                        </label>
                                        <textarea
                                            id="purpose"
                                            value={data.purpose}
                                            onChange={(e) => setData('purpose', e.target.value)}
                                            rows={3}
                                            className={`mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 ${
                                                errors.purpose ? 'border-red-500' : ''
                                            }`}
                                        />
                                        {errors.purpose && (
                                            <p className="mt-1 text-sm text-red-600">{errors.purpose}</p>
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
                                            href="/admin/reservations"
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
