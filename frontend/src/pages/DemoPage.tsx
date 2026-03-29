import { useState } from 'react'
import { generateReviewsApi } from '../api/reviews'

export default function DemoPage() {
  const [count, setCount] = useState(10)
  const [ratingMin, setRatingMin] = useState(3)
  const [ratingMax, setRatingMax] = useState(5)
  const [productId, setProductId] = useState('')
  const [loading, setLoading] = useState(false)
  const [message, setMessage] = useState('')
  const [error, setError] = useState('')

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault()
    setMessage('')
    setError('')

    if (ratingMin > ratingMax) {
      setError('Min rating must be ≤ max rating.')
      return
    }

    setLoading(true)
    try {
      await generateReviewsApi(count, ratingMin, ratingMax, productId || undefined)
      setMessage(
        `Dispatched ${count} fake review${count !== 1 ? 's' : ''} to the queue. ` +
        `They'll appear once the consumer processes them.`
      )
    } catch {
      setError('Failed to dispatch reviews. Is the backend running?')
    } finally {
      setLoading(false)
    }
  }

  return (
    <div className="container py-4" style={{ maxWidth: 600 }}>
      <h2 className="mb-1">Demo — Generate Fake Reviews</h2>
      <p className="text-muted mb-4">
        Dispatches a <code>GenerateFakeReviewsMessage</code> to RabbitMQ. The consumer worker
        creates reviews with random users, products, and Faker-generated text.
      </p>

      <div className="card shadow-sm mb-4">
        <div className="card-body">
          <form onSubmit={handleSubmit}>
            <div className="mb-3">
              <label className="form-label">Number of reviews</label>
              <input
                type="number"
                className="form-control"
                value={count}
                min={1}
                max={500}
                onChange={(e) => setCount(Number(e.target.value))}
                required
              />
            </div>
            <div className="row g-3 mb-3">
              <div className="col">
                <label className="form-label">Min rating</label>
                <select className="form-select" value={ratingMin} onChange={(e) => setRatingMin(Number(e.target.value))}>
                  {[1, 2, 3, 4, 5].map((n) => <option key={n} value={n}>{n}</option>)}
                </select>
              </div>
              <div className="col">
                <label className="form-label">Max rating</label>
                <select className="form-select" value={ratingMax} onChange={(e) => setRatingMax(Number(e.target.value))}>
                  {[1, 2, 3, 4, 5].map((n) => <option key={n} value={n}>{n}</option>)}
                </select>
              </div>
            </div>
            <div className="mb-3">
              <label className="form-label">
                Product UUID <span className="text-muted fw-normal">(optional — random if blank)</span>
              </label>
              <input
                type="text"
                className="form-control"
                value={productId}
                onChange={(e) => setProductId(e.target.value)}
                placeholder="e.g. 018e1234-5678-7000-abcd-ef0123456789"
                pattern="[0-9a-fA-F]{8}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{12}|"
              />
            </div>
            {error && <div className="alert alert-danger py-2">{error}</div>}
            {message && <div className="alert alert-success py-2">{message}</div>}
            <button type="submit" className="btn btn-primary" disabled={loading}>
              {loading ? 'Dispatching…' : 'Generate Reviews'}
            </button>
          </form>
        </div>
      </div>

      <div className="card border-success">
        <div className="card-body">
          <h6 className="card-title">How it works</h6>
          <ol className="mb-0 small">
            <li>This form calls <code>POST /api/demo/generate-reviews</code></li>
            <li>The backend dispatches a <code>GenerateFakeReviewsMessage</code> to RabbitMQ</li>
            <li>The consumer container picks it up and creates reviews using Faker</li>
            <li>Each review triggers a <code>ReviewAddedMessage</code>, updating the Redis rating cache</li>
          </ol>
        </div>
      </div>
    </div>
  )
}
