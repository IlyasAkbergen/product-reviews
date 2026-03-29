import { useState } from 'react'

interface Props {
  value: string
  onChange: (value: string) => void
}

export default function SearchBar({ value, onChange }: Props) {
  const [local, setLocal] = useState(value)

  const submit = (e: React.FormEvent) => {
    e.preventDefault()
    onChange(local)
  }

  return (
    <form onSubmit={submit} className="d-flex gap-2">
      <input
        type="text"
        className="form-control"
        placeholder="Search products…"
        value={local}
        onChange={(e) => setLocal(e.target.value)}
      />
      <button type="submit" className="btn btn-primary">Search</button>
    </form>
  )
}
