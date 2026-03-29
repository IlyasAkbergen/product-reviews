import { client } from './client'
import type { PaginatedReviews } from '../types'

export const getReviewsApi = (productId: string, page = 1, limit = 10) =>
  client.get<PaginatedReviews>(`/api/products/${productId}/reviews`, {
    params: { page, limit },
  })

export const addReviewApi = (productId: string, rating: number, body: string) =>
  client.post(`/api/products/${productId}/reviews`, { rating, body })

export const generateReviewsApi = (
  count: number,
  ratingMin: number,
  ratingMax: number,
  productId?: string,
) =>
  client.post('/api/demo/generate-reviews', {
    count,
    ratingMin,
    ratingMax,
    ...(productId ? { productId } : {}),
  })
