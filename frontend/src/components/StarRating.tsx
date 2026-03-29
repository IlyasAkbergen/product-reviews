interface Props {
  value: number
  size?: number
}

export default function StarRating({ value, size = 16 }: Props) {
  return (
    <span style={{ fontSize: size, lineHeight: 1, whiteSpace: 'nowrap' }} aria-label={`${value} out of 5`}>
      {[1, 2, 3, 4, 5].map((i) => {
        const fill = Math.min(Math.max(value - (i - 1), 0), 1)
        return (
          <span key={i} className="star-overlay-container">
            <span className="star-grey">★</span>
            <span className="star-overlay-fill" style={{ width: `${fill * 100}%` }}>★</span>
          </span>
        )
      })}
    </span>
  )
}
