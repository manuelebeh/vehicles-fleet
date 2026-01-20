import { Head, Link, useForm } from '@inertiajs/react';
import AdminLayout from '../../../Layouts/AdminLayout';
import Input from '../../../Components/Input';
import Select from '../../../Components/Select';
import Button from '../../../Components/Button';

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
        <AdminLayout auth={auth}>
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
                                    <Input
                                        type="text"
                                        id="brand"
                                        name="brand"
                                        label="Marque"
                                        required
                                        value={data.brand}
                                        onChange={(e) => setData('brand', e.target.value)}
                                        error={errors.brand}
                                        maxLength={100}
                                    />

                                    {/* Model */}
                                    <Input
                                        type="text"
                                        id="model"
                                        name="model"
                                        label="Modèle"
                                        required
                                        value={data.model}
                                        onChange={(e) => setData('model', e.target.value)}
                                        error={errors.model}
                                        maxLength={100}
                                    />

                                    {/* License Plate */}
                                    <Input
                                        type="text"
                                        id="license_plate"
                                        name="license_plate"
                                        label="Plaque d'immatriculation"
                                        required
                                        value={data.license_plate}
                                        onChange={(e) => setData('license_plate', e.target.value.toUpperCase())}
                                        error={errors.license_plate}
                                        maxLength={30}
                                        className="font-mono"
                                    />

                                    {/* Year */}
                                    <Input
                                        type="number"
                                        id="year"
                                        name="year"
                                        label="Année"
                                        value={data.year}
                                        onChange={(e) => setData('year', e.target.value ? parseInt(e.target.value) : '')}
                                        error={errors.year}
                                        min="1900"
                                        max={new Date().getFullYear() + 1}
                                    />

                                    {/* Color */}
                                    <Input
                                        type="text"
                                        id="color"
                                        name="color"
                                        label="Couleur"
                                        value={data.color}
                                        onChange={(e) => setData('color', e.target.value)}
                                        error={errors.color}
                                        maxLength={50}
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
                                            href="/admin/vehicles"
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
