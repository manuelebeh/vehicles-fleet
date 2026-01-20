import { Head, Link, useForm } from '@inertiajs/react';
import AdminLayout from '../../../Layouts/AdminLayout';
import Input from '../../../Components/Input';
import Button from '../../../Components/Button';

export default function UsersEdit({ auth, user, roles }) {
    const { data, setData, put, processing, errors } = useForm({
        email: user.email || '',
        first_name: user.first_name || '',
        last_name: user.last_name || '',
        role_ids: user.roles ? user.roles.map((role) => role.id) : [],
    });

    const handleSubmit = (e) => {
        e.preventDefault();
        put(`/admin/users/${user.id}`);
    };

    const handleRoleToggle = (roleId) => {
        const currentRoleIds = Array.isArray(data.role_ids) ? data.role_ids : [];
        if (currentRoleIds.includes(roleId)) {
            setData('role_ids', currentRoleIds.filter((id) => id !== roleId));
        } else {
            setData('role_ids', [...currentRoleIds, roleId]);
        }
    };

    return (
        <AdminLayout auth={auth}>
            <Head title={`Modifier ${user.email}`} />
            <div className="py-12">
                <div className="max-w-2xl mx-auto sm:px-6 lg:px-8">
                    <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div className="p-6">
                            <div className="flex justify-between items-center mb-6">
                                <h1 className="text-3xl font-bold text-gray-900">
                                    Modifier l'utilisateur
                                </h1>
                                <Link
                                    href="/admin/users"
                                    className="text-gray-600 hover:text-gray-900"
                                >
                                    ← Retour
                                </Link>
                            </div>

                            <form onSubmit={handleSubmit}>
                                <div className="space-y-6">
                                    {/* Email */}
                                    <Input
                                        type="email"
                                        id="email"
                                        name="email"
                                        label="Email"
                                        value={data.email}
                                        onChange={(e) => setData('email', e.target.value)}
                                        error={errors.email}
                                    />

                                    {/* First Name */}
                                    <Input
                                        type="text"
                                        id="first_name"
                                        name="first_name"
                                        label="Prénom"
                                        value={data.first_name}
                                        onChange={(e) => setData('first_name', e.target.value)}
                                        error={errors.first_name}
                                        maxLength={100}
                                    />

                                    {/* Last Name */}
                                    <Input
                                        type="text"
                                        id="last_name"
                                        name="last_name"
                                        label="Nom"
                                        value={data.last_name}
                                        onChange={(e) => setData('last_name', e.target.value)}
                                        error={errors.last_name}
                                        maxLength={100}
                                    />

                                    {/* Roles */}
                                    <div>
                                        <label className="block text-sm font-medium text-gray-700 mb-2">
                                            Rôles
                                        </label>
                                        <div className="space-y-2">
                                            {roles.map((role) => (
                                                <label key={role.id} className="flex items-center">
                                                    <input
                                                        type="checkbox"
                                                        checked={Array.isArray(data.role_ids) && data.role_ids.includes(role.id)}
                                                        onChange={() => handleRoleToggle(role.id)}
                                                        className="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                                    />
                                                    <span className="ml-2 text-sm text-gray-700">
                                                        {role.display_name || role.name}
                                                        {role.description && (
                                                            <span className="text-gray-500 ml-1">
                                                                - {role.description}
                                                            </span>
                                                        )}
                                                    </span>
                                                </label>
                                            ))}
                                        </div>
                                        {errors.role_ids && (
                                            <p className="mt-1 text-sm text-red-600">{errors.role_ids}</p>
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
                                            href="/admin/users"
                                            className="px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
                                        >
                                            Annuler
                                        </Link>
                                        <Button
                                            type="submit"
                                            processing={processing}
                                        >
                                            Mettre à jour
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
