export interface User {
    id: number;
    nik: string;
    karyawan: {
        nama: string;
        suskel: string;
        ptkp: string;
        kode_unit: string;
        sub_unit: string;
        egrup: string;
        esubgrup: string;
        jabatan: string;
        jenkel: string;
        pendidikan: string;
        tanggal_masuk: string;
        tanggal_cuti_tahunan: string | null;
        tanggal_cuti_panjang: string | null;
        tanggal_lahir: string;
        bod: string;
        no_hp: string | null;
        unit_usaha: {
            nama_unit: string;
            kode_unit: any;
        };
    };
    is_spi: boolean;
}

export type PageProps<
    T extends Record<string, unknown> = Record<string, unknown>
> = T & {
    auth: {
        user: User;
    };
};
