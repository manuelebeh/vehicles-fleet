import { Head, Link, useForm } from '@inertiajs/react';
import Input from '../Components/Input';
import Button from '../Components/Button';

export default function Login({ status, canResetPassword }) {
    const { data, setData, post, processing, errors, reset } = useForm({
        email: '',
        password: '',
    });

    const submit = (e) => {
        e.preventDefault();
        post('/login', {
            onFinish: () => reset('password'),
        });
    };

    return (
        <>
            <Head title="Connexion" />

            <div className="min-h-screen flex items-center justify-center bg-gray-50 py-12 px-4 sm:px-6 lg:px-8">
                <div className="max-w-md w-full space-y-8">
                    <div>
                        <h2 className="mt-6 text-center text-3xl font-extrabold text-gray-900">
                            Connexion à votre compte
                        </h2>
                    </div>
                    <form className="mt-8 space-y-6" onSubmit={submit}>
                        {status && (
                            <div className="rounded-md bg-green-50 p-4">
                                <div className="text-sm text-green-800">{status}</div>
                            </div>
                        )}

                        <div className="space-y-4">
                            <Input
                                type="email"
                                id="email"
                                name="email"
                                label="Email"
                                required
                                value={data.email}
                                onChange={(e) => setData('email', e.target.value)}
                                error={errors.email}
                                placeholder="Adresse email"
                                autoComplete="email"
                            />
                            <Input
                                type="password"
                                id="password"
                                name="password"
                                label="Mot de passe"
                                required
                                value={data.password}
                                onChange={(e) => setData('password', e.target.value)}
                                error={errors.password}
                                placeholder="Mot de passe"
                                autoComplete="current-password"
                            />
                        </div>

                        {errors.message && (
                            <div className="rounded-md bg-red-50 p-4">
                                <div className="text-sm text-red-800">{errors.message}</div>
                            </div>
                        )}

                        <div>
                            <Button
                                type="submit"
                                processing={processing}
                                className="w-full"
                            >
                                Se connecter
                            </Button>
                        </div>
                    </form>
                </div>
            </div>
        </>
    );
}
