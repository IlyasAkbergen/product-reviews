export interface User {
  id: string
  email: string
  name: string
}

export interface AuthState {
  token: string | null
  user: User | null
}

export interface ProductSummary {
  id: string
  externalId: number
  title: string
  description: string
  price: number
  category: string
  thumbnail: string | null
  brand: string | null
  averageRating: number | null
  reviewCount: number
}

export interface ProductDetail extends ProductSummary {}

export interface ReviewItem {
  id: string
  userId: string
  userName: string | null
  rating: number
  body: string
  createdAt: string
}

export interface PaginatedProducts {
  items: ProductSummary[]
  total: number
  page: number
  limit: number
}

export interface PaginatedReviews {
  items: ReviewItem[]
  total: number
  page: number
  limit: number
}

export interface ProductFilters {
  search: string
  category: string
  minPrice: string
  maxPrice: string
  page: number
  limit: number
}
