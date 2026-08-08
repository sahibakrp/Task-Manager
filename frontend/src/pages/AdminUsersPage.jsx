import { useEffect, useState } from 'react';
import { useAuth } from '../hooks/useAuth';
import { Link } from 'react-router-dom';

const ROLE_LABELS = {
  1: 'Admin',
  2: 'User',
};

export default function AdminUsersPage() {
  const { authAxios, user } = useAuth();
  const [users, setUsers] = useState([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);

  useEffect(() => {
    if (user?.role_id !== 1) {
      setError('Access denied. Admins only.');
      setLoading(false);
      return;
    }

    const loadUsers = async () => {
      try {
        const response = await authAxios.get('/users');
        setUsers(response.data || []);
      } catch (err) {
        setError(err.response?.data?.message || 'Unable to load users');
      } finally {
        setLoading(false);
      }
    };

    loadUsers();
  }, [authAxios, user]);

  return (
    <div className="min-h-screen bg-gray-100">
      <div className="max-w-6xl mx-auto px-4 py-10">
        <div className="flex items-center justify-between mb-8">
          <div>
            <h1 className="text-2xl font-bold text-gray-900">Admin: User Management</h1>
            <p className="text-gray-500 mt-1">View and inspect registered users.</p>
          </div>
          <Link
            to="/"
            className="px-4 py-2.5 text-sm font-semibold text-white bg-indigo-600 rounded-lg transition hover:bg-indigo-700"
          >
            Back to Dashboard
          </Link>
        </div>

        {loading ? (
          <p className="text-gray-500">Loading users…</p>
        ) : error ? (
          <div className="bg-red-50 text-red-600 text-sm border border-red-200 rounded-lg px-4 py-3">
            {error}
          </div>
        ) : (
          <div className="overflow-x-auto bg-white rounded-2xl shadow-sm border border-gray-100">
            <table className="min-w-full divide-y divide-gray-200">
              <thead className="bg-gray-50">
                <tr>
                  <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                  <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                  <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Email</th>
                  <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Role</th>
                  <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Created</th>
                </tr>
              </thead>
              <tbody className="bg-white divide-y divide-gray-200">
                {users.map((row) => (
                  <tr key={row.id}>
                    <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-700">{row.id}</td>
                    <td className="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{row.name}</td>
                    <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-700">{row.email}</td>
                    <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-700">{ROLE_LABELS[row.role_id] || 'User'}</td>
                    <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-700">{row.created_at}</td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        )}
      </div>
    </div>
  );
}
