import { client } from './client'
import type { PaginatedProducts, ProductDetail } from '../types'

export interface ProductsParams {
  search?: string
  category?: string
  minPrice?: string
  maxPrice?: string
  page?: number
  limit?: number
}

export const getProductsApi = (params: ProductsParams) =>
  client.get<PaginatedProducts>('/api/products', { params })

export const getProductApi = (id: string) =>
  client.get<ProductDetail>(`/api/products/${id}`)

export const getCategoriesApi = () =>
  client.get<string[]>('/api/categories')
