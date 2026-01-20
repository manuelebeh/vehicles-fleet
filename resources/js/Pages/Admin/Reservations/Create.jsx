import { Head, Link, useForm } from '@inertiajs/react';
import AdminLayout from '../../../Layouts/AdminLayout';
import Select from '../../../Components/Select';
import DateInput from '../../../Components/DateInput';
import Textarea from '../../../Components/Textarea';
import Button from '../../../Components/Button';

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
        <AdminLayout auth={auth}>
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
                                    <Select
                                        id="user_id"
                                        name="user_id"
                                        label="Utilisateur"
                                        required
                                        value={data.user_id}
                                        onChange={(e) => setData('user_id', e.target.value)}
                                        error={errors.user_id}
                                        placeholder="Sélectionner un utilisateur"
                                        options={users.map((user) => ({
                                            value: user.id,
                                            label: `${user.email} ${user.first_name || user.last_name ? `(${user.first_name || ''} ${user.last_name || ''})`.trim() : ''}`,
                                        }))}
                                    />

                                    {/* Vehicle */}
                                    <Select
                                        id="vehicle_id"
                                        name="vehicle_id"
                                        label="Véhicule"
                                        required
                                        value={data.vehicle_id}
                                        onChange={(e) => setData('vehicle_id', e.target.value)}
                                        error={errors.vehicle_id}
                                        placeholder="Sélectionner un véhicule"
                                        options={vehicles.map((vehicle) => ({
                                            value: vehicle.id,
                                            label: `${vehicle.brand} ${vehicle.model} - ${vehicle.license_plate}`,
                                        }))}
                                    />

                                    {/* Start Date */}
                                    <DateInput
                                        id="start_date"
                                        name="start_date"
                                        label="Date et heure de début"
                                        type="datetime-local"
                                        required
                                        value={data.start_date}
                                        onChange={(e) => setData('start_date', e.target.value)}
                                        error={errors.start_date}
                                    />

                                    {/* End Date */}
                                    <DateInput
                                        id="end_date"
                                        name="end_date"
                                        label="Date et heure de fin"
                                        type="datetime-local"
                                        required
                                        value={data.end_date}
                                        onChange={(e) => setData('end_date', e.target.value)}
                                        error={errors.end_date}
                                    />

                                    {/* Purpose */}
                                    <Textarea
                                        id="purpose"
                                        name="purpose"
                                        label="Motif"
                                        value={data.purpose}
                                        onChange={(e) => setData('purpose', e.target.value)}
                                        error={errors.purpose}
                                        rows={3}
                                    />

                                    {/* Status */}
                                    <Select
                                        id="status"
                                        name="status"
                                        label="Statut"
                                        value={data.status}
                                        onChange={(e) => setData('status', e.target.value)}
                                        error={errors.status}
                                        options={statuses.map((status) => ({
                                            value: status,
                                            label: getStatusLabel(status),
                                        }))}
                                    />

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
                                        <Button
                                            type="submit"
                                            processing={processing}
                                        >
                                            Créer
                                        </Button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </AdminLayout>
    );
}
