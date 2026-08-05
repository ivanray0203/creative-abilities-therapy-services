import { useState } from 'react';

import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

interface EnterInputProps {
    inputs: string[];
    setInputs: (inputs: string[]) => void;
    title: string;
    error?: string;
}

/**
 * "Type and press Enter to add a tag" input, ported 1:1 from
 * cats-frontend/src/components/EnterInput.tsx. Used by the career
 * application form's Skills field.
 */
export function EnterInput({ inputs, setInputs, error, title }: EnterInputProps) {
    const [current, setCurrent] = useState('');

    const handleAdd = () => {
        const trimmed = current.trim();

        if (trimmed && !inputs.includes(trimmed)) {
            setInputs([...inputs, trimmed]);
            setCurrent('');
        }
    };

    const handleKeyDown = (e: React.KeyboardEvent<HTMLInputElement>) => {
        if (e.key === 'Enter') {
            e.preventDefault();
            handleAdd();
        }
    };

    const handleRemove = (value: string) => {
        setInputs(inputs.filter((v) => v !== value));
    };

    return (
        <div className="mb-4">
            <Label>
                {title} <span className="text-red-700">*</span>
            </Label>
            <Input
                value={current}
                onChange={(e) => setCurrent(e.target.value)}
                onKeyDown={handleKeyDown}
                className="mt-2 rounded-[10px]"
                placeholder={`Type a ${title} and press Enter`}
            />
            <div className="mt-2 flex flex-wrap gap-2">
                {inputs.map((value) => (
                    <div key={value} className="flex items-center rounded-[5px] bg-primary/20 px-2 py-1 text-primary">
                        {value}
                        <button type="button" className="ml-1 font-bold" onClick={() => handleRemove(value)}>
                            ×
                        </button>
                    </div>
                ))}
            </div>
            {error && <p className="text-sm text-red-600">{error}</p>}
        </div>
    );
}

export default EnterInput;
