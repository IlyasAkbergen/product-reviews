import { useEffect, useState, useCallback } from 'react'
import { Link } from 'react-router-dom'
import { getProductsApi, getCategoriesApi } from '../api/products'
import type { ProductSummary } from '../types'
import SearchBar from '../components/SearchBar'
import CategoryFilter from '../components/CategoryFilter'
import PriceRangeFilter from '../components/PriceRangeFilter'
import Pagination from '../components/Pagination'
import StarRating from '../components/StarRating'

const LIMIT = 12

export default function ProductsPage() {
  const [items, setItems] = useState<ProductSummary[]>([])
  const [total, setTotal] = useState(0)
  const [page, setPage] = useState(1)
  const [search, setSearch] = useState('')
  const [category, setCategory] = useState('')
  const [minPrice, setMinPrice] = useState('')
  const [maxPrice, setMaxPrice] = useState('')
  const [categories, setCategories] = useState<string[]>([])
  const [loading, setLoading] = useState(false)
  const [error, setError] = useState('')

  useEffect(() => {
    getCategoriesApi()
      .then(({ data }) => setCategories(data))
      .catch(() => {/* non-fatal: filter simply shows no options */})
  }, [])

  const fetchProducts = useCallback(async () => {
    setLoading(true)
    setError('')
    try {
      const params = {
        page,
        limit: LIMIT,
        ...(search ? { search } : {}),
        ...(category ? { category } : {}),
        ...(minPrice ? { minPrice } : {}),
        ...(maxPrice ? { maxPrice } : {}),
      }
      const { data } = await getProductsApi(params)
      setItems(data.items)
      setTotal(data.total)
    } catch {
      setError('Failed to load products.')
    } finally {
      setLoading(false)
    }
  }, [page, search, category, minPrice, maxPrice])

  useEffect(() => { fetchProducts() }, [fetchProducts])

  const handleSearch = (val: string) => { setSearch(val); setPage(1) }
  const handleCategory = (val: string) => { setCategory(val); setPage(1) }
  const handlePrice = (min: string, max: string) => { setMinPrice(min); setMaxPrice(max); setPage(1) }

  return (
    <div className="container py-4">
      <h2 className="mb-4">Products</h2>

      <div className="d-flex flex-wrap gap-2 mb-4 align-items-center">
        <div className="flex-grow-1" style={{ minWidth: 200 }}>
          <SearchBar value={search} onChange={handleSearch} />
        </div>
        <CategoryFilter value={category} options={categories} onChange={handleCategory} />
        <PriceRangeFilter minPrice={minPrice} maxPrice={maxPrice} onChange={handlePrice} />
      </div>

      {loading && <p className="text-muted">Loading…</p>}
      {error && <div className="alert alert-danger">{error}</div>}
      {!loading && !error && items.length === 0 && (
        <p className="text-muted">No products found.</p>
      )}

      <div className="row row-cols-1 row-cols-sm-2 row-cols-md-3 row-cols-xl-4 g-3">
        {items.map((p) => (
          <div key={p.id} className="col">
            <Link to={`/products/${p.id}`} className="text-decoration-none text-dark">
              <div className="card h-100 shadow-sm">
                {p.thumbnail && (
                  <img
                    src={p.thumbnail}
                    alt={p.title}
                    className="card-img-top"
                    style={{ height: 160, objectFit: 'cover' }}
                  />
                )}
                <div className="card-body d-flex flex-column">
                  <h6 className="card-title">{p.title}</h6>
                  <small className="text-muted text-capitalize mb-auto">{p.category}</small>
                  <div className="d-flex justify-content-between align-items-center mt-2">
                    <span className="fw-bold text-primary">${p.price.toFixed(2)}</span>
                    <span className="d-flex align-items-center gap-1">
                      <StarRating value={typeof p.averageRating === 'number' ? p.averageRating : 0} size={13} />
                      <small className="text-muted">({p.reviewCount})</small>
                    </span>
                  </div>
                </div>
              </div>
            </Link>
          </div>
        ))}
      </div>

      <Pagination page={page} total={total} limit={LIMIT} onChange={setPage} />
    </div>
  )
}
