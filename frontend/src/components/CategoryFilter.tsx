interface Props {
  value: string
  options: string[]
  onChange: (value: string) => void
}

export default function CategoryFilter({ value, options, onChange }: Props) {
  return (
    <select
      className="form-select"
      value={value}
      onChange={(e) => onChange(e.target.value)}
    >
      <option value="">All categories</option>
      {options.map((cat) => (
        <option key={cat} value={cat}>{cat}</option>
      ))}
    </select>
  )
}
