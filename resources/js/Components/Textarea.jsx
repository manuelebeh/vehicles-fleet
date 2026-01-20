export default function Textarea({
    id,
    name,
    label,
    required = false,
    value,
    onChange,
    error,
    placeholder,
    rows = 3,
    maxLength,
    className = '',
    ...props
}) {
    const textareaId = id || name;
    const hasError = !!error;
    
    const textareaClasses = `py-2 px-5 mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 ${
        hasError ? 'border-red-500' : ''
    } ${className}`;
    
    return (
        <div>
            {label && (
                <label htmlFor={textareaId} className="block text-sm font-medium text-gray-700">
                    {label}
                    {required && <span className="text-red-500 ml-1">*</span>}
                </label>
            )}
            <textarea
                id={textareaId}
                name={name}
                value={value}
                onChange={onChange}
                placeholder={placeholder}
                required={required}
                rows={rows}
                maxLength={maxLength}
                className={textareaClasses}
                {...props}
            />
            {error && (
                <p className="mt-1 text-sm text-red-600">{error}</p>
            )}
        </div>
    );
}
