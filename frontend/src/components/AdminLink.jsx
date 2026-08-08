import { Link } from 'react-router-dom';
import { useAuth } from '../hooks/useAuth';

export default function AdminLink() {
  const { user } = useAuth();

  if (!user || user.role_id !== 1) {
    return null;
  }

  return (
    <Link
      to="/admin/users"
      className="inline-flex items-center px-3 py-2 rounded-lg bg-emerald-600 text-sm font-semibold text-white hover:bg-emerald-700"
    >
      Admin Users
    </Link>
  );
}
