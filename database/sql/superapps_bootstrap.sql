CREATE SCHEMA IF NOT EXISTS hris;

CREATE SEQUENCE IF NOT EXISTS hris.unit_usaha_id_seq;
CREATE SEQUENCE IF NOT EXISTS hris.bagian_id_seq;
CREATE SEQUENCE IF NOT EXISTS hris.sub_bagian_id_seq;
CREATE SEQUENCE IF NOT EXISTS hris.karyawan_id_seq;
CREATE SEQUENCE IF NOT EXISTS hris.holiday_id_seq;
CREATE SEQUENCE IF NOT EXISTS public.users_id_seq;
CREATE SEQUENCE IF NOT EXISTS public.user_access_id_seq;

CREATE TABLE IF NOT EXISTS hris.unit_usaha (
    id bigint DEFAULT nextval('hris.unit_usaha_id_seq'::regclass) NOT NULL,
    kode_unit character varying(10) NOT NULL,
    nama_unit character varying(50) NOT NULL,
    kode_grup_unit character varying(50) NOT NULL,
    nama_grup_unit character varying(50) NOT NULL,
    is_saturday_on boolean NOT NULL,
    is_head_office boolean NOT NULL,
    created_at timestamp without time zone,
    updated_at timestamp without time zone
);

CREATE TABLE IF NOT EXISTS hris.bagian (
    id bigint DEFAULT nextval('hris.bagian_id_seq'::regclass) NOT NULL,
    name character varying(255) NOT NULL,
    code character varying(255) NOT NULL,
    created_at timestamp without time zone,
    updated_at timestamp without time zone,
    kode_unit character varying
);

CREATE TABLE IF NOT EXISTS hris.sub_bagian (
    id bigint DEFAULT nextval('hris.sub_bagian_id_seq'::regclass) NOT NULL,
    bagian_code character varying(255) NOT NULL,
    name character varying(255) NOT NULL,
    code character varying(255) NOT NULL,
    created_at timestamp without time zone,
    updated_at timestamp without time zone
);

CREATE TABLE IF NOT EXISTS hris.karyawan (
    id bigint DEFAULT nextval('hris.karyawan_id_seq'::regclass) NOT NULL,
    nik character varying(15) NOT NULL,
    nama character varying(50) NOT NULL,
    suskel character varying(10),
    ptkp character varying(10),
    kode_unit character varying(10) NOT NULL,
    sub_unit character varying(50),
    egrup character varying(50),
    esubgrup character varying(50),
    jabatan character varying(100) NOT NULL,
    jenkel character varying(20) NOT NULL,
    pendidikan character varying(50),
    tanggal_masuk date,
    tanggal_cuti_tahunan date,
    tanggal_cuti_panjang date,
    tanggal_lahir date,
    bod character varying(10) NOT NULL,
    created_at timestamp without time zone,
    updated_at timestamp without time zone,
    no_hp character varying
);

CREATE TABLE IF NOT EXISTS hris.holiday (
    id bigint DEFAULT nextval('hris.holiday_id_seq'::regclass) NOT NULL,
    name character varying,
    date date,
    type character varying,
    created_at timestamp without time zone,
    updated_at timestamp without time zone
);

CREATE TABLE IF NOT EXISTS public.users (
    id bigint DEFAULT nextval('public.users_id_seq'::regclass) NOT NULL,
    nik character varying(255) NOT NULL,
    password character varying(255) NOT NULL,
    remember_token character varying(100),
    created_at timestamp without time zone,
    updated_at timestamp without time zone
);

CREATE TABLE IF NOT EXISTS public.user_access (
    id bigint DEFAULT nextval('public.user_access_id_seq'::regclass) NOT NULL,
    created_at character varying,
    updated_at character varying,
    nik character varying,
    aplikasi character varying
);

ALTER SEQUENCE IF EXISTS hris.unit_usaha_id_seq OWNED BY hris.unit_usaha.id;
ALTER SEQUENCE IF EXISTS hris.bagian_id_seq OWNED BY hris.bagian.id;
ALTER SEQUENCE IF EXISTS hris.sub_bagian_id_seq OWNED BY hris.sub_bagian.id;
ALTER SEQUENCE IF EXISTS hris.karyawan_id_seq OWNED BY hris.karyawan.id;
ALTER SEQUENCE IF EXISTS hris.holiday_id_seq OWNED BY hris.holiday.id;
ALTER SEQUENCE IF EXISTS public.users_id_seq OWNED BY public.users.id;
ALTER SEQUENCE IF EXISTS public.user_access_id_seq OWNED BY public.user_access.id;

DO $$
BEGIN
    ALTER TABLE ONLY hris.unit_usaha ADD CONSTRAINT unit_usaha_pkey PRIMARY KEY (id);
EXCEPTION
    WHEN duplicate_object OR invalid_table_definition THEN NULL;
END $$;

DO $$
BEGIN
    ALTER TABLE ONLY hris.unit_usaha ADD CONSTRAINT hris_unit_usaha_kode_unit_unique UNIQUE (kode_unit);
EXCEPTION
    WHEN duplicate_object THEN NULL;
END $$;

DO $$
BEGIN
    ALTER TABLE ONLY hris.bagian ADD CONSTRAINT bagian_pkey PRIMARY KEY (id);
EXCEPTION
    WHEN duplicate_object OR invalid_table_definition THEN NULL;
END $$;

DO $$
BEGIN
    ALTER TABLE ONLY hris.bagian ADD CONSTRAINT hris_bagian_code_unique UNIQUE (code);
EXCEPTION
    WHEN duplicate_object THEN NULL;
END $$;

DO $$
BEGIN
    ALTER TABLE ONLY hris.sub_bagian ADD CONSTRAINT sub_bagian_pkey PRIMARY KEY (id);
EXCEPTION
    WHEN duplicate_object OR invalid_table_definition THEN NULL;
END $$;

DO $$
BEGIN
    ALTER TABLE ONLY hris.sub_bagian ADD CONSTRAINT hris_sub_bagian_code_unique UNIQUE (code);
EXCEPTION
    WHEN duplicate_object THEN NULL;
END $$;

DO $$
BEGIN
    ALTER TABLE ONLY hris.karyawan ADD CONSTRAINT karyawan_pkey PRIMARY KEY (id);
EXCEPTION
    WHEN duplicate_object OR invalid_table_definition THEN NULL;
END $$;

DO $$
BEGIN
    ALTER TABLE ONLY hris.karyawan ADD CONSTRAINT hris_karyawan_nik_unique UNIQUE (nik);
EXCEPTION
    WHEN duplicate_object THEN NULL;
END $$;

DO $$
BEGIN
    ALTER TABLE ONLY hris.holiday ADD CONSTRAINT holiday_pk PRIMARY KEY (id);
EXCEPTION
    WHEN duplicate_object OR invalid_table_definition THEN NULL;
END $$;

DO $$
BEGIN
    ALTER TABLE ONLY public.users ADD CONSTRAINT users_pkey PRIMARY KEY (id);
EXCEPTION
    WHEN duplicate_object OR invalid_table_definition THEN NULL;
END $$;

DO $$
BEGIN
    ALTER TABLE ONLY public.users ADD CONSTRAINT users_nik_unique UNIQUE (nik);
EXCEPTION
    WHEN duplicate_object THEN NULL;
END $$;

DO $$
BEGIN
    ALTER TABLE ONLY public.user_access ADD CONSTRAINT user_access_pk PRIMARY KEY (id);
EXCEPTION
    WHEN duplicate_object OR invalid_table_definition THEN NULL;
END $$;

CREATE INDEX IF NOT EXISTS user_access_nik_aplikasi_index ON public.user_access USING btree (nik, aplikasi);
