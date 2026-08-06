import { useMemo, useState, type InputHTMLAttributes } from 'react'
import { Eye, EyeOff } from 'lucide-react'
import { cn } from '@/lib/utils'

const REQUIREMENTS = [
  { key: 'length', label: 'At least 8 characters', test: (p: string) => p.length >= 8 },
  { key: 'upper', label: 'At least one uppercase letter', test: (p: string) => /[A-Z]/.test(p) },
  { key: 'lower', label: 'At least one lowercase letter', test: (p: string) => /[a-z]/.test(p) },
  { key: 'number', label: 'At least one number', test: (p: string) => /[0-9]/.test(p) },
  { key: 'special', label: 'At least one special character', test: (p: string) => /[^A-Za-z0-9]/.test(p) },
]

const COMMON = new Set([
  'password', 'password1', 'password123', '12345678', 'qwerty123', 'admin123', 'welcome1',
])

export function evaluatePasswordStrength(password: string) {
  const met = REQUIREMENTS.filter(r => r.test(password)).map(r => r.key)
  const lower = password.toLowerCase()

  if (!password) return { level: 0, label: 'Very Weak', score: 0, met }
  if (password.length < 4 || COMMON.has(lower) || /^(.)\1{5,}$/.test(password)) {
    return { level: 1, label: 'Very Weak', score: 12, met }
  }
  if (met.length <= 2 || password.length < 6) return { level: 2, label: 'Weak', score: 32, met }
  if (met.length <= 3 || password.length < 8) return { level: 3, label: 'Medium', score: 52, met }
  if (met.length === 4) return { level: 3, label: 'Medium', score: 62, met }
  if (met.length === 5 && password.length < 12) return { level: 4, label: 'Strong', score: 82, met }
  return { level: 5, label: 'Very Strong', score: 100, met }
}

export function allPasswordRequirementsMet(password: string) {
  return REQUIREMENTS.every(r => r.test(password))
}

const strengthColors: Record<number, string> = {
  1: 'text-red-600 dark:text-red-400',
  2: 'text-orange-600 dark:text-orange-400',
  3: 'text-yellow-600 dark:text-yellow-400',
  4: 'text-emerald-600 dark:text-emerald-400',
  5: 'text-emerald-700 dark:text-emerald-300',
}

interface PasswordFieldProps extends Omit<InputHTMLAttributes<HTMLInputElement>, 'type'> {
  label?: string
  error?: string
  showStrength?: boolean
}

export function PasswordField({ label, error, id, className, showStrength, value = '', onChange, ...props }: PasswordFieldProps) {
  const [visible, setVisible] = useState(false)
  const password = String(value)
  const strength = useMemo(() => evaluatePasswordStrength(password), [password])

  return (
    <div className="space-y-1.5">
      {label && (
        <label htmlFor={id} className="text-sm font-medium text-slate-700 dark:text-slate-300">{label}</label>
      )}
      <div className="relative">
        <input
          id={id}
          type={visible ? 'text' : 'password'}
          value={value}
          onChange={onChange}
          className={cn(
            'flex h-11 w-full rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 px-4 py-2 pr-11 text-sm text-slate-900 dark:text-slate-100 transition-colors placeholder:text-slate-400 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-transparent',
            error && 'border-red-500 focus:ring-red-500',
            className
          )}
          {...props}
        />
        <button
          type="button"
          onClick={() => setVisible(v => !v)}
          className="absolute right-3 top-1/2 -translate-y-1/2 min-w-[2rem] min-h-[2rem] flex items-center justify-center rounded-lg text-slate-400 hover:text-emerald-600 dark:hover:text-emerald-400 hover:bg-emerald-50 dark:hover:bg-emerald-900/20 transition-colors cursor-pointer"
          aria-label={visible ? 'Hide password' : 'Show password'}
          aria-pressed={visible}
        >
          {visible ? <EyeOff className="w-4 h-4" /> : <Eye className="w-4 h-4" />}
        </button>
      </div>

      {showStrength && (
        <div className="mt-2 p-3.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800/60" aria-live="polite">
          <p className="text-xs text-slate-600 dark:text-slate-400 mb-2">
            Password strength:{' '}
            <strong className={cn('font-semibold', strengthColors[strength.level] || strengthColors[1])}>
              {strength.label}
            </strong>
          </p>
          <div className="h-1.5 rounded-full bg-slate-200 dark:bg-slate-700 overflow-hidden mb-3" role="progressbar" aria-valuenow={strength.score} aria-valuemin={0} aria-valuemax={100}>
            <div
              className="h-full rounded-full bg-gradient-to-r from-red-500 via-yellow-500 to-emerald-500 transition-all duration-300 ease-out"
              style={{ width: `${strength.score}%` }}
            />
          </div>
          <p className="text-xs font-semibold text-slate-600 dark:text-slate-400 mb-1.5">Password must contain:</p>
          <ul className="grid grid-cols-1 sm:grid-cols-2 gap-1">
            {REQUIREMENTS.map(req => {
              const ok = req.test(password)
              return (
                <li key={req.key} className={cn('flex items-start gap-2 text-xs', ok ? 'text-slate-800 dark:text-slate-200' : 'text-slate-500 dark:text-slate-400')}>
                  <span className={cn('font-bold w-4 text-center', ok ? 'text-emerald-600 dark:text-emerald-400' : '')} aria-hidden="true">
                    {ok ? '✓' : '○'}
                  </span>
                  {req.label}
                </li>
              )
            })}
          </ul>
        </div>
      )}

      {error && <p className="text-xs text-red-500">{error}</p>}
    </div>
  )
}

interface ConfirmPasswordFieldProps extends Omit<InputHTMLAttributes<HTMLInputElement>, 'type'> {
  label?: string
  password: string
  error?: string
}

export function ConfirmPasswordField({ label, password, error, id, className, value = '', onChange, ...props }: ConfirmPasswordFieldProps) {
  const [visible, setVisible] = useState(false)
  const confirm = String(value)
  const showMatch = confirm.length > 0
  const matches = password === confirm && confirm.length > 0

  return (
    <div className="space-y-1.5">
      {label && (
        <label htmlFor={id} className="text-sm font-medium text-slate-700 dark:text-slate-300">{label}</label>
      )}
      <div className="relative">
        <input
          id={id}
          type={visible ? 'text' : 'password'}
          value={value}
          onChange={onChange}
          className={cn(
            'flex h-11 w-full rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 px-4 py-2 pr-11 text-sm text-slate-900 dark:text-slate-100 transition-colors placeholder:text-slate-400 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-transparent',
            error && 'border-red-500 focus:ring-red-500',
            className
          )}
          {...props}
        />
        <button
          type="button"
          onClick={() => setVisible(v => !v)}
          className="absolute right-3 top-1/2 -translate-y-1/2 min-w-[2rem] min-h-[2rem] flex items-center justify-center rounded-lg text-slate-400 hover:text-emerald-600 dark:hover:text-emerald-400 hover:bg-emerald-50 dark:hover:bg-emerald-900/20 transition-colors cursor-pointer"
          aria-label={visible ? 'Hide password' : 'Show password'}
          aria-pressed={visible}
        >
          {visible ? <EyeOff className="w-4 h-4" /> : <Eye className="w-4 h-4" />}
        </button>
      </div>
      {showMatch && (
        <p className={cn('text-xs font-medium flex items-center gap-1.5', matches ? 'text-emerald-600 dark:text-emerald-400' : 'text-red-600 dark:text-red-400')} aria-live="polite">
          <span aria-hidden="true">{matches ? '✓' : '✕'}</span>
          {matches ? 'Passwords match' : 'Passwords do not match'}
        </p>
      )}
      {error && <p className="text-xs text-red-500">{error}</p>}
    </div>
  )
}
