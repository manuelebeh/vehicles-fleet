import { Head, Link, router } from '@inertiajs/react';
import AppLayout from '../../../Layouts/AppLayout';

export default function UsersShow({ auth, user, roles }) {
    const handleDelete = () => {
        if (confirm(`Êtes-vous sûr de vouloir supprimer l'utilisateur ${user.email} ?`)) {
            router.delete(`/admin/users/${user.id}`);
        }
    };

    return (
        <AppLayout auth={auth}>
            <Head title={`Utilisateur: ${user.email}`} />
            <div className="py-12">
                <div className="max-w-4xl mx-auto sm:px-6 lg:px-8">
                    <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div className="p-6">
                            <div className="flex justify-between items-center mb-6">
                                <h1 className="text-3xl font-bold text-gray-900">
                                    Détails de l'utilisateur
                                </h1>
                                <div className="flex gap-2">
                                    <Link
                                        href="/admin/users"
                                        className="text-gray-600 hover:text-gray-900"
                                    >
                                        ← Retour
                                    </Link>
                                    <Link
                                        href={`/admin/users/${user.id}/edit`}
                                        className="px-4 py-2 bg-yellow-600 text-white rounded-md hover:bg-yellow-700"
                                    >
                                        Modifier
                                    </Link>
                                    <button
                                        onClick={handleDelete}
                                        className="px-4 py-2 bg-red-600 text-white rounded-md hover:bg-red-700"
                                    >
                                        Supprimer
                                    </button>
                                </div>
                            </div>

                            <div className="space-y-6">
                                {/* Informations générales */}
                                <div>
                                    <h2 className="text-xl font-semibold text-gray-900 mb-4">
                                        Informations générales
                                    </h2>
                                    <dl className="grid grid-cols-1 gap-x-4 gap-y-6 sm:grid-cols-2">
                                        <div>
                                            <dt className="text-sm font-medium text-gray-500">ID</dt>
                                            <dd className="mt-1 text-sm text-gray-900">{user.id}</dd>
                                        </div>
                                        <div>
                                            <dt className="text-sm font-medium text-gray-500">Email</dt>
                                            <dd className="mt-1 text-sm text-gray-900">{user.email}</dd>
                                        </div>
                                        <div>
                                            <dt className="text-sm font-medium text-gray-500">Prénom</dt>
                                            <dd className="mt-1 text-sm text-gray-900">
                                                {user.first_name || '-'}
                                            </dd>
                                        </div>
                                        <div>
                                            <dt className="text-sm font-medium text-gray-500">Nom</dt>
                                            <dd className="mt-1 text-sm text-gray-900">
                                                {user.last_name || '-'}
                                            </dd>
                                        </div>
                                        <div>
                                            <dt className="text-sm font-medium text-gray-500">Créé le</dt>
                                            <dd className="mt-1 text-sm text-gray-900">
                                                {user.created_at
                                                    ? new Date(user.created_at).toLocaleString('fr-FR')
                                                    : '-'}
                                            </dd>
                                        </div>
                                        <div>
                                            <dt className="text-sm font-medium text-gray-500">Modifié le</dt>
                                            <dd className="mt-1 text-sm text-gray-900">
                                                {user.updated_at
                                                    ? new Date(user.updated_at).toLocaleString('fr-FR')
                                                    : '-'}
                                            </dd>
                                        </div>
                                    </dl>
                                </div>

                                {/* Mot de passe */}
                                <div>
                                    <h2 className="text-xl font-semibold text-gray-900 mb-4">
                                        Mot de passe
                                    </h2>
                                    <div className="flex items-center gap-4">
                                        <p className="text-gray-700">
                                            Le mot de passe peut être régénéré pour cet utilisateur.
                                        </p>
                                        <button
                                            onClick={() => {
                                                if (confirm('Êtes-vous sûr de vouloir régénérer le mot de passe de cet utilisateur ? Le mot de passe actuel sera perdu.')) {
                                                    router.post(`/admin/users/${user.id}/regenerate-password`);
                                                }
                                            }}
                                            className="px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700 text-sm font-medium"
                                        >
                                            Régénérer le mot de passe
                                        </button>
                                    </div>
                                </div>

                                {/* Rôles */}
                                <div>
                                    <h2 className="text-xl font-semibold text-gray-900 mb-4">
                                        Rôles
                                    </h2>
                                    {user.roles && user.roles.length > 0 ? (
                                        <div className="flex flex-wrap gap-2">
                                            {user.roles.map((role) => (
                                                <span
                                                    key={role.id}
                                                    className="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-indigo-100 text-indigo-800"
                                                >
                                                    {role.display_name || role.name}
                                                    {role.description && (
                                                        <span className="ml-2 text-xs text-indigo-600">
                                                            ({role.description})
                                                        </span>
                                                    )}
                                                </span>
                                            ))}
                                        </div>
                                    ) : (
                                        <p className="text-gray-500">Aucun rôle assigné</p>
                                    )}
                                </div>

                                {/* Réservations */}
                                {user.reservations_count !== undefined && (
                                    <div>
                                        <h2 className="text-xl font-semibold text-gray-900 mb-4">
                                            Réservations
                                        </h2>
                                        <p className="text-gray-700">
                                            Nombre de réservations :{' '}
                                            <span className="font-semibold">
                                                {user.reservations_count || 0}
                                            </span>
                                        </p>
                                    </div>
                                )}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}
