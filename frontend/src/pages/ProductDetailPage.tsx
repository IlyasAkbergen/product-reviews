import { useEffect, useState } from 'react'
import { useParams, Link } from 'react-router-dom'
import { useSelector } from 'react-redux'
import type { RootState } from '../store'
import { getProductApi } from '../api/products'
import { getReviewsApi, addReviewApi } from '../api/reviews'
import type { ProductDetail, ReviewItem } from '../types'
import StarRating from '../components/StarRating'
import Pagination from '../components/Pagination'

const REVIEWS_LIMIT = 10

export default function ProductDetailPage() {
  const { id } = useParams<{ id: string }>()
  const token = useSelector((s: RootState) => s.auth.token)

  const [product, setProduct] = useState<ProductDetail | null>(null)
  const [reviews, setReviews] = useState<ReviewItem[]>([])
  const [reviewTotal, setReviewTotal] = useState(0)
  const [reviewPage, setReviewPage] = useState(1)
  const [productLoading, setProductLoading] = useState(true)
  const [reviewsLoading, setReviewsLoading] = useState(false)

  const [rating, setRating] = useState(5)
  const [body, setBody] = useState('')
  const [submitError, setSubmitError] = useState('')
  const [submitSuccess, setSubmitSuccess] = useState('')
  const [submitting, setSubmitting] = useState(false)

  useEffect(() => {
    if (!id) return
    setProductLoading(true)
    getProductApi(id).then(({ data }) => setProduct(data)).finally(() => setProductLoading(false))
  }, [id])

  useEffect(() => {
    if (!id) return
    setReviewsLoading(true)
    getReviewsApi(id, reviewPage, REVIEWS_LIMIT)
      .then(({ data }) => { setReviews(data.items); setReviewTotal(data.total) })
      .finally(() => setReviewsLoading(false))
  }, [id, reviewPage])

  const handleAddReview = async (e: React.FormEvent) => {
    e.preventDefault()
    if (!id) return
    setSubmitError('')
    setSubmitSuccess('')
    setSubmitting(true)
    try {
      await addReviewApi(id, rating, body)
      setBody('')
      setRating(5)
      setSubmitSuccess('Review submitted! It will appear shortly.')
      const { data } = await getReviewsApi(id, 1, REVIEWS_LIMIT)
      setReviews(data.items)
      setReviewTotal(data.total)
      setReviewPage(1)
      const { data: updated } = await getProductApi(id)
      setProduct(updated)
    } catch (err: unknown) {
      const status = (err as { response?: { status?: number } })?.response?.status
      if (status === 409) {
        setSubmitError('You have already reviewed this product.')
      } else {
        setSubmitError('Failed to submit review. Please try again.')
      }
    } finally {
      setSubmitting(false)
    }
  }

  if (productLoading) return <div className="container py-4"><p className="text-muted">Loading…</p></div>
  if (!product) return <div className="container py-4"><div className="alert alert-danger">Product not found.</div></div>

  const averageRating = typeof product.averageRating === 'number' ? product.averageRating : 0

  return (
    <div className="container py-4">
      <Link to="/products" className="btn btn-link ps-0 mb-3">&larr; Back to products</Link>

      <div className="row g-4 mb-5">
        <div className="col-md-5">
          {product.thumbnail && (
            <img src={product.thumbnail} alt={product.title} className="img-fluid rounded border" />
          )}
        </div>
        <div className="col-md-7">
          <h2>{product.title}</h2>
          {product.brand && (
            <p className="text-muted mb-1">{product.brand} &middot; <span className="text-capitalize">{product.category}</span></p>
          )}
          <p className="mt-2">{product.description}</p>
          <div className="d-flex align-items-center gap-3 my-3">
            <span className="fs-3 fw-bold text-primary">${product.price.toFixed(2)}</span>
            <span className="d-flex align-items-center gap-1">
              <StarRating value={averageRating} size={18} />
              <span className="text-muted small">{averageRating.toFixed(1)} ({product.reviewCount} reviews)</span>
            </span>
          </div>
        </div>
      </div>

      <h4 className="mb-3">Reviews</h4>

      {token ? (
        <div className="card mb-4" style={{ maxWidth: 600 }}>
          <div className="card-body">
            <h6 className="card-title">Write a Review</h6>
            <form onSubmit={handleAddReview}>
              <div className="mb-2 d-flex align-items-center gap-1">
                {[1, 2, 3, 4, 5].map((n) => (
                  <button
                    key={n}
                    type="button"
                    onClick={() => setRating(n)}
                    className={`star-btn ${n <= rating ? 'active' : ''}`}
                    aria-label={`${n} star${n > 1 ? 's' : ''}`}
                  >
                    ★
                  </button>
                ))}
                <span className="text-muted small ms-1">{rating}/5</span>
              </div>
              <div className="mb-2">
                <textarea
                  className="form-control"
                  value={body}
                  onChange={(e) => setBody(e.target.value)}
                  placeholder="Share your thoughts about this product…"
                  required
                  rows={3}
                />
              </div>
              {submitError && <div className="alert alert-danger py-2">{submitError}</div>}
              {submitSuccess && <div className="alert alert-success py-2">{submitSuccess}</div>}
              <button type="submit" className="btn btn-primary" disabled={submitting}>
                {submitting ? 'Submitting…' : 'Submit Review'}
              </button>
            </form>
          </div>
        </div>
      ) : (
        <p className="text-muted mb-4">
          <Link to="/login">Sign in</Link> to write a review.
        </p>
      )}

      {reviewsLoading && <p className="text-muted">Loading reviews…</p>}
      {!reviewsLoading && reviews.length === 0 && <p className="text-muted">No reviews yet. Be the first!</p>}

      <div className="d-flex flex-column gap-3">
        {reviews.map((r) => (
          <div key={r.id} className="card">
            <div className="card-body">
              <div className="d-flex align-items-center gap-2 mb-2 flex-wrap">
                <strong>{r.userName ?? 'Anonymous'}</strong>
                <StarRating value={r.rating} size={14} />
                <span className="text-muted small ms-auto">
                  {new Date(r.createdAt).toLocaleDateString()}
                </span>
              </div>
              <p className="mb-0">{r.body}</p>
            </div>
          </div>
        ))}
      </div>

      <Pagination page={reviewPage} total={reviewTotal} limit={REVIEWS_LIMIT} onChange={setReviewPage} />
    </div>
  )
}
