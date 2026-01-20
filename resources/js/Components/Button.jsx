export default function Button({
    type = 'button',
    variant = 'primary',
    size = 'md',
    disabled = false,
    processing = false,
    children,
    className = '',
    ...props
}) {
    const baseClasses = 'inline-flex items-center justify-center border font-medium rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-offset-2 transition ease-in-out duration-150 disabled:opacity-50 disabled:cursor-not-allowed';
    
    const variantClasses = {
        primary: 'border-transparent text-white bg-indigo-600 hover:bg-indigo-700 focus:ring-indigo-500',
        secondary: 'border-gray-300 text-gray-700 bg-white hover:bg-gray-50 focus:ring-indigo-500',
        danger: 'border-transparent text-white bg-red-600 hover:bg-red-700 focus:ring-red-500',
        success: 'border-transparent text-white bg-green-600 hover:bg-green-700 focus:ring-green-500',
        warning: 'border-transparent text-white bg-yellow-600 hover:bg-yellow-700 focus:ring-yellow-500',
        info: 'border-transparent text-white bg-blue-600 hover:bg-blue-700 focus:ring-blue-500',
    };
    
    const sizeClasses = {
        sm: 'px-3 py-1.5 text-xs',
        md: 'px-4 py-2 text-sm',
        lg: 'px-6 py-3 text-base',
    };
    
    const classes = `${baseClasses} ${variantClasses[variant]} ${sizeClasses[size]} ${className}`;
    
    return (
        <button
            type={type}
            disabled={disabled || processing}
            className={classes}
            {...props}
        >
            {processing ? 'Chargement...' : children}
        </button>
    );
}
