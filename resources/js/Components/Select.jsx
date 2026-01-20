export default function Select({
    id,
    name,
    label,
    required = false,
    value,
    onChange,
    error,
    options = [],
    placeholder = 'Sélectionner...',
    className = '',
    ...props
}) {
    const selectId = id || name;
    const hasError = !!error;
    
    const selectClasses = `py-2 px-5 mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 ${
        hasError ? 'border-red-500' : ''
    } ${className}`;
    
    return (
        <div>
            {label && (
                <label htmlFor={selectId} className="block text-sm font-medium text-gray-700">
                    {label}
                    {required && <span className="text-red-500 ml-1">*</span>}
                </label>
            )}
            <select
                id={selectId}
                name={name}
                value={value}
                onChange={onChange}
                required={required}
                className={selectClasses}
                {...props}
            >
                {placeholder && (
                    <option value="">{placeholder}</option>
                )}
                {options.map((option) => {
                    if (typeof option === 'string') {
                        return (
                            <option key={option} value={option}>
                                {option}
                            </option>
                        );
                    }
                    return (
                        <option key={option.value} value={option.value}>
                            {option.label}
                        </option>
                    );
                })}
            </select>
            {error && (
                <p className="mt-1 text-sm text-red-600">{error}</p>
            )}
        </div>
    );
}
