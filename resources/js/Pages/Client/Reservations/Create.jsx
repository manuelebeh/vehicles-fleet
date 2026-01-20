import { Head, Link, useForm, router } from '@inertiajs/react';
import UserLayout from '../../../Layouts/UserLayout';
import Select from '../../../Components/Select';
import DateInput from '../../../Components/DateInput';
import Textarea from '../../../Components/Textarea';
import Button from '../../../Components/Button';

export default function ClientReservationsCreate({ auth, vehicles = [] }) {
    const urlParams = new URLSearchParams(window.location.search);
    const preselectedVehicleId = urlParams.get('vehicle_id');

    const { data, setData, post, processing, errors } = useForm({
        vehicle_id: preselectedVehicleId || '',
        start_date: '',
        end_date: '',
        purpose: '',
    });

    const handleSubmit = (e) => {
        e.preventDefault();
        post('/reservations');
    };

    return (
        <UserLayout auth={auth}>
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
                                    href="/vehicles"
                                    className="text-gray-600 hover:text-gray-900"
                                >
                                    ← Retour
                                </Link>
                            </div>

                            <form onSubmit={handleSubmit}>
                                <div className="space-y-6">
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
                                        label="Motif (optionnel)"
                                        value={data.purpose}
                                        onChange={(e) => setData('purpose', e.target.value)}
                                        error={errors.purpose}
                                        rows={3}
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
                                            href="/vehicles"
                                            className="px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
                                        >
                                            Annuler
                                        </Link>
                                        <Button
                                            type="submit"
                                            processing={processing}
                                        >
                                            Créer la réservation
                                        </Button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </UserLayout>
    );
}
