import { ref } from 'vue'
import apiClient from '../services/api'

// Global State
const user = ref({
  name: 'Chamroeun',
  coins: 250,
  streak_days: 7,
  rank_title: 'Hanuman IV',
  studied_hours: 2.5,
  target_hours: 4.0
})

const isUserLoading = ref(false)

export function useUser() {
  const fetchUserProfile = async () => {
    try {
      isUserLoading.value = true
      const res = await apiClient.get('/user/profile')
      if (res.data) {
        user.value = res.data
      }
    } catch (err) {
      console.warn('Cannot fetch user from DB, using local state:', err)
    } finally {
      isUserLoading.value = false
    }
  }

  const addReward = (earnedCoins, newHours) => {
    user.value.coins += earnedCoins
    if (newHours) user.value.studied_hours = newHours
  }

  const deductCoins = (amount) => {
    user.value.coins = Math.max(0, user.value.coins - amount)
  }

  return {
    user,
    isUserLoading,
    fetchUserProfile,
    addReward,
    deductCoins
  }
}
