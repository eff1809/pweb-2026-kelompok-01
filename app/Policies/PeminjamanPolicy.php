<?php

namespace App\Policies;

use App\Models\Peminjaman;
use App\Models\User;

class PeminjamanPolicy
{
    /**
     * Admin dapat melihat seluruh peminjaman; Mahasiswa hanya miliknya sendiri.
     */
    public function view(User $user, Peminjaman $peminjaman): bool
    {
        return $user->isAdmin() || $peminjaman->user_id === $user->id;
    }

    /**
     * Hanya Mahasiswa yang dapat mengajukan peminjaman.
     */
    public function create(User $user): bool
    {
        return $user->isMahasiswa();
    }

    /**
     * Mahasiswa hanya boleh mengubah peminjamannya sendiri selama status masih diajukan.
     */
    public function update(User $user, Peminjaman $peminjaman): bool
    {
        return $user->isMahasiswa()
            && $peminjaman->user_id === $user->id
            && $peminjaman->isDiajukan();
    }

    /**
     * Mahasiswa hanya boleh membatalkan peminjamannya sendiri selama status masih diajukan.
     */
    public function batal(User $user, Peminjaman $peminjaman): bool
    {
        return $user->isMahasiswa()
            && $peminjaman->user_id === $user->id
            && $peminjaman->isDiajukan();
    }

    /**
     * Admin hanya boleh menyetujui pengajuan berstatus diajukan.
     */
    public function setujui(User $user, Peminjaman $peminjaman): bool
    {
        return $user->isAdmin() && $peminjaman->isDiajukan();
    }

    /**
     * Admin hanya boleh menolak pengajuan berstatus diajukan.
     */
    public function tolak(User $user, Peminjaman $peminjaman): bool
    {
        return $user->isAdmin() && $peminjaman->isDiajukan();
    }

    /**
     * Admin hanya boleh mencatat penyerahan untuk peminjaman berstatus disetujui.
     */
    public function serahkan(User $user, Peminjaman $peminjaman): bool
    {
        return $user->isAdmin() && $peminjaman->isDisetujui();
    }

    /**
     * Admin hanya boleh mencatat pengembalian untuk peminjaman berstatus dipinjam.
     */
    public function kembalikan(User $user, Peminjaman $peminjaman): bool
    {
        return $user->isAdmin() && $peminjaman->isDipinjam();
    }
}
