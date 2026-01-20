export default function DateInput({
    id,
    name,
    label,
    required = false,
    value,
    onChange,
    error,
    type = 'date',
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
    
    const formatValue = (val) => {
        if (!val) return '';
        if (type === 'datetime-local') {
            const date = new Date(val);
            if (isNaN(date.getTime())) return '';
            const year = date.getFullYear();
            const month = String(date.getMonth() + 1).padStart(2, '0');
            const day = String(date.getDate()).padStart(2, '0');
            const hours = String(date.getHours()).padStart(2, '0');
            const minutes = String(date.getMinutes()).padStart(2, '0');
            return `${year}-${month}-${day}T${hours}:${minutes}`;
        }
        return val;
    };
    
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
                value={formatValue(value)}
                onChange={onChange}
                required={required}
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
