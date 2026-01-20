import { usePage } from '@inertiajs/react';
import { useState } from 'react';

export default function FlashMessages() {
    const { flash } = usePage().props;
    const [dismissedPassword, setDismissedPassword] = useState(false);

    const copyPassword = () => {
        if (flash?.generated_password) {
            navigator.clipboard.writeText(flash.generated_password);
            alert('Mot de passe copié dans le presse-papiers !');
        }
    };

    const showPasswordAlert = flash?.generated_password && flash?.user_email && !dismissedPassword;

    return (
        <>
            {showPasswordAlert && (
                <div className="mb-4 bg-yellow-50 border-l-4 border-yellow-400 p-4 rounded">
                    <div className="flex">
                        <div className="flex-shrink-0">
                            <svg className="h-5 w-5 text-yellow-400" viewBox="0 0 20 20" fill="currentColor">
                                <path fillRule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clipRule="evenodd" />
                            </svg>
                        </div>
                        <div className="ml-3 flex-1">
                            <p className="text-sm text-yellow-700">
                                <strong>Utilisateur créé avec succès !</strong>
                            </p>
                            <p className="mt-1 text-sm text-yellow-700">
                                Email : <strong>{flash.user_email}</strong>
                            </p>
                            <p className="mt-1 text-sm text-yellow-700">
                                Mot de passe généré : <strong className="font-mono">{flash.generated_password}</strong>
                            </p>
                            <p className="mt-2 text-xs text-yellow-600">
                                ⚠️ Notez ce mot de passe, il ne sera plus affiché après fermeture de cette alerte.
                            </p>
                            <div className="mt-3 flex gap-2">
                                <button
                                    onClick={copyPassword}
                                    className="text-sm bg-yellow-100 text-yellow-800 px-3 py-1 rounded hover:bg-yellow-200"
                                >
                                    Copier le mot de passe
                                </button>
                                <button
                                    onClick={() => setDismissedPassword(true)}
                                    className="text-sm bg-yellow-100 text-yellow-800 px-3 py-1 rounded hover:bg-yellow-200"
                                >
                                    Fermer
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            )}

            {flash?.success && !showPasswordAlert && (
                <div className="mb-4 bg-green-50 border-l-4 border-green-400 p-4 rounded">
                    <div className="flex">
                        <div className="flex-shrink-0">
                            <svg className="h-5 w-5 text-green-400" viewBox="0 0 20 20" fill="currentColor">
                                <path fillRule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clipRule="evenodd" />
                            </svg>
                        </div>
                        <div className="ml-3">
                            <p className="text-sm text-green-700">{flash.success}</p>
                        </div>
                    </div>
                </div>
            )}

            {flash?.error && (
                <div className="mb-4 bg-red-50 border-l-4 border-red-400 p-4 rounded">
                    <div className="flex">
                        <div className="flex-shrink-0">
                            <svg className="h-5 w-5 text-red-400" viewBox="0 0 20 20" fill="currentColor">
                                <path fillRule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clipRule="evenodd" />
                            </svg>
                        </div>
                        <div className="ml-3">
                            <p className="text-sm text-red-700">{flash.error}</p>
                        </div>
                    </div>
                </div>
            )}
        </>
    );
}
