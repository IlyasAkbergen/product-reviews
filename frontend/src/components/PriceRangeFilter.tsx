interface Props {
  minPrice: string
  maxPrice: string
  onChange: (min: string, max: string) => void
}

export default function PriceRangeFilter({ minPrice, maxPrice, onChange }: Props) {
  return (
    <div className="input-group" style={{ width: 220 }}>
      <span className="input-group-text">$</span>
      <input
        type="number"
        className="form-control"
        placeholder="Min"
        value={minPrice}
        min={0}
        onChange={(e) => onChange(e.target.value, maxPrice)}
      />
      <span className="input-group-text">–</span>
      <input
        type="number"
        className="form-control"
        placeholder="Max"
        value={maxPrice}
        min={0}
        onChange={(e) => onChange(minPrice, e.target.value)}
      />
    </div>
  )
}
