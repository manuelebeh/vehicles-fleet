export default function Input({
    type = 'text',
    id,
    name,
    label,
    required = false,
    value,
    onChange,
    error,
    placeholder,
    maxLength,
    min,
    max,
    className = '',
    ...props
}) {
    const inputId = id || name;
    const hasError = !!error;
    
    const inputClasses = `py-2 px-5 mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 ${
        hasError ? 'border-red-500' : ''
    } ${className}`;
    
    return (
        <div>
            {label && (
                <label htmlFor={inputId} className="block text-sm font-medium text-gray-700">
                    {label}
                    {required && <span className="text-red-500 ml-1">*</span>}
                </label>
            )}
            <input
                type={type}
                id={inputId}
                name={name}
                value={value}
                onChange={onChange}
                placeholder={placeholder}
                required={required}
                maxLength={maxLength}
                min={min}
                max={max}
                className={inputClasses}
                {...props}
            />
            {error && (
                <p className="mt-1 text-sm text-red-600">{error}</p>
            )}
        </div>
    );
}
