--
-- PostgreSQL database dump
--

\restrict yvOWUsNcQThbdHc4Mlirygf0Oy94UboWOdhUn8hlgGYAVklvP5Kp5C8nWS9fczH

-- Dumped from database version 17.7
-- Dumped by pg_dump version 17.7

-- Started on 2026-03-24 13:15:13

SET statement_timeout = 0;
SET lock_timeout = 0;
SET idle_in_transaction_session_timeout = 0;
SET transaction_timeout = 0;
SET client_encoding = 'UTF8';
SET standard_conforming_strings = on;
SELECT pg_catalog.set_config('search_path', '', false);
SET check_function_bodies = false;
SET xmloption = content;
SET client_min_messages = warning;
SET row_security = off;

SET default_tablespace = '';

SET default_table_access_method = heap;

--
-- TOC entry 217 (class 1259 OID 172914)
-- Name: access_tokens; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.access_tokens (
    id integer NOT NULL,
    user_id character varying(20) NOT NULL,
    token character varying(255) NOT NULL,
    ip_address character varying(45),
    user_agent text,
    created_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP,
    expires_at timestamp without time zone NOT NULL,
    is_revoked smallint DEFAULT 0,
    trial548 character(1)
);


ALTER TABLE public.access_tokens OWNER TO postgres;

--
-- TOC entry 220 (class 1259 OID 172946)
-- Name: admin_notification_dismissals; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.admin_notification_dismissals (
    id integer NOT NULL,
    admin_id character varying(20) NOT NULL,
    notification_key character varying(120) NOT NULL,
    dismissed_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL,
    trial548 character(1)
);


ALTER TABLE public.admin_notification_dismissals OWNER TO postgres;

--
-- TOC entry 219 (class 1259 OID 172945)
-- Name: admin_notification_dismissals_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.admin_notification_dismissals_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.admin_notification_dismissals_id_seq OWNER TO postgres;

--
-- TOC entry 5669 (class 0 OID 0)
-- Dependencies: 219
-- Name: admin_notification_dismissals_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.admin_notification_dismissals_id_seq OWNED BY public.admin_notification_dismissals.id;


--
-- TOC entry 222 (class 1259 OID 172964)
-- Name: announcements; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.announcements (
    id integer NOT NULL,
    type character varying(12) NOT NULL,
    title character varying(255) NOT NULL,
    message text NOT NULL,
    subtitle character varying(255),
    button_text character varying(100),
    button_link character varying(500),
    image character varying(500),
    background_color character varying(20) DEFAULT '#000000'::character varying,
    text_color character varying(20) DEFAULT '#ffffff'::character varying,
    icon character varying(50),
    priority integer DEFAULT 0,
    is_active smallint DEFAULT 1,
    start_date timestamp without time zone,
    end_date timestamp without time zone,
    created_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP,
    updated_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP,
    trial548 character(1)
);


ALTER TABLE public.announcements OWNER TO postgres;

--
-- TOC entry 221 (class 1259 OID 172963)
-- Name: announcements_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.announcements_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.announcements_id_seq OWNER TO postgres;

--
-- TOC entry 5670 (class 0 OID 0)
-- Dependencies: 221
-- Name: announcements_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.announcements_id_seq OWNED BY public.announcements.id;


--
-- TOC entry 223 (class 1259 OID 172990)
-- Name: audit_categories; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.audit_categories (
    audit_id integer NOT NULL,
    category_id integer,
    action_type character varying(10),
    old_name character varying(100),
    new_name character varying(100),
    action_date timestamp without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL,
    trial548 character(1)
);


ALTER TABLE public.audit_categories OWNER TO postgres;

--
-- TOC entry 225 (class 1259 OID 173001)
-- Name: audit_orders; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.audit_orders (
    id integer NOT NULL,
    orden_id integer NOT NULL,
    accion character varying(10) NOT NULL,
    usuario_id character varying(20),
    sql_usuario character varying(255),
    fecha timestamp without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL,
    detalles text,
    trial548 character(1)
);


ALTER TABLE public.audit_orders OWNER TO postgres;

--
-- TOC entry 224 (class 1259 OID 173000)
-- Name: audit_orders_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.audit_orders_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.audit_orders_id_seq OWNER TO postgres;

--
-- TOC entry 5671 (class 0 OID 0)
-- Dependencies: 224
-- Name: audit_orders_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.audit_orders_id_seq OWNED BY public.audit_orders.id;


--
-- TOC entry 227 (class 1259 OID 173023)
-- Name: audit_users; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.audit_users (
    id integer NOT NULL,
    usuario_id character varying(20) NOT NULL,
    accion character varying(10) NOT NULL,
    usuario_modificador character varying(20),
    sql_usuario character varying(255),
    fecha timestamp without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL,
    detalles text,
    trial548 character(1)
);


ALTER TABLE public.audit_users OWNER TO postgres;

--
-- TOC entry 226 (class 1259 OID 173022)
-- Name: audit_users_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.audit_users_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.audit_users_id_seq OWNER TO postgres;

--
-- TOC entry 5672 (class 0 OID 0)
-- Dependencies: 226
-- Name: audit_users_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.audit_users_id_seq OWNED BY public.audit_users.id;


--
-- TOC entry 229 (class 1259 OID 173045)
-- Name: bank_account_config; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.bank_account_config (
    id integer NOT NULL,
    bank_code character varying(10) NOT NULL,
    account_number character varying(50) NOT NULL,
    account_type character varying(9) NOT NULL,
    account_holder character varying(100) NOT NULL,
    identification_type character varying(3) DEFAULT 'cc'::character varying NOT NULL,
    identification_number character varying(20) NOT NULL,
    email character varying(100),
    phone character varying(15),
    is_active smallint DEFAULT 1,
    created_by character varying(20) NOT NULL,
    created_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP,
    updated_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP,
    trial548 character(1)
);


ALTER TABLE public.bank_account_config OWNER TO postgres;

--
-- TOC entry 228 (class 1259 OID 173044)
-- Name: bank_account_config_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.bank_account_config_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.bank_account_config_id_seq OWNER TO postgres;

--
-- TOC entry 5673 (class 0 OID 0)
-- Dependencies: 228
-- Name: bank_account_config_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.bank_account_config_id_seq OWNED BY public.bank_account_config.id;


--
-- TOC entry 231 (class 1259 OID 173064)
-- Name: bulk_discount_rules; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.bulk_discount_rules (
    id integer NOT NULL,
    min_quantity integer NOT NULL,
    max_quantity integer,
    discount_percentage numeric(5,2) NOT NULL,
    is_active smallint DEFAULT 1,
    created_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP,
    updated_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP,
    trial548 character(1)
);


ALTER TABLE public.bulk_discount_rules OWNER TO postgres;

--
-- TOC entry 230 (class 1259 OID 173063)
-- Name: bulk_discount_rules_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.bulk_discount_rules_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.bulk_discount_rules_id_seq OWNER TO postgres;

--
-- TOC entry 5674 (class 0 OID 0)
-- Dependencies: 230
-- Name: bulk_discount_rules_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.bulk_discount_rules_id_seq OWNED BY public.bulk_discount_rules.id;


--
-- TOC entry 232 (class 1259 OID 173081)
-- Name: cache; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.cache (
    key character varying(255) NOT NULL,
    value text NOT NULL,
    expiration integer NOT NULL,
    trial548 character(1)
);


ALTER TABLE public.cache OWNER TO postgres;

--
-- TOC entry 233 (class 1259 OID 173088)
-- Name: cache_locks; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.cache_locks (
    key character varying(255) NOT NULL,
    owner character varying(255) NOT NULL,
    expiration integer NOT NULL,
    trial548 character(1)
);


ALTER TABLE public.cache_locks OWNER TO postgres;

--
-- TOC entry 235 (class 1259 OID 173096)
-- Name: cart_items; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.cart_items (
    id integer NOT NULL,
    cart_id integer NOT NULL,
    product_id integer NOT NULL,
    color_variant_id integer,
    size_variant_id integer,
    quantity integer DEFAULT 1 NOT NULL,
    created_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP,
    updated_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP,
    trial548 character(1)
);


ALTER TABLE public.cart_items OWNER TO postgres;

--
-- TOC entry 234 (class 1259 OID 173095)
-- Name: cart_items_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.cart_items_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.cart_items_id_seq OWNER TO postgres;

--
-- TOC entry 5675 (class 0 OID 0)
-- Dependencies: 234
-- Name: cart_items_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.cart_items_id_seq OWNED BY public.cart_items.id;


--
-- TOC entry 237 (class 1259 OID 173114)
-- Name: carts; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.carts (
    id integer NOT NULL,
    user_id character varying(50),
    session_id character varying(255),
    created_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP,
    updated_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP,
    trial551 character(1)
);


ALTER TABLE public.carts OWNER TO postgres;

--
-- TOC entry 236 (class 1259 OID 173113)
-- Name: carts_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.carts_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.carts_id_seq OWNER TO postgres;

--
-- TOC entry 5676 (class 0 OID 0)
-- Dependencies: 236
-- Name: carts_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.carts_id_seq OWNED BY public.carts.id;


--
-- TOC entry 239 (class 1259 OID 173131)
-- Name: categories; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.categories (
    id integer NOT NULL,
    name character varying(100) NOT NULL,
    slug character varying(100) NOT NULL,
    description text,
    image character varying(255),
    parent_id integer,
    is_active smallint DEFAULT 1,
    created_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP,
    updated_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP,
    trial551 character(1)
);


ALTER TABLE public.categories OWNER TO postgres;

--
-- TOC entry 238 (class 1259 OID 173130)
-- Name: categories_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.categories_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.categories_id_seq OWNER TO postgres;

--
-- TOC entry 5677 (class 0 OID 0)
-- Dependencies: 238
-- Name: categories_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.categories_id_seq OWNED BY public.categories.id;


--
-- TOC entry 241 (class 1259 OID 173155)
-- Name: collections; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.collections (
    id integer NOT NULL,
    name character varying(100) NOT NULL,
    slug character varying(100) NOT NULL,
    description text,
    image character varying(255),
    launch_date date,
    is_active smallint DEFAULT 1,
    created_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP,
    updated_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP,
    trial551 character(1)
);


ALTER TABLE public.collections OWNER TO postgres;

--
-- TOC entry 240 (class 1259 OID 173154)
-- Name: collections_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.collections_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.collections_id_seq OWNER TO postgres;

--
-- TOC entry 5678 (class 0 OID 0)
-- Dependencies: 240
-- Name: collections_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.collections_id_seq OWNED BY public.collections.id;


--
-- TOC entry 243 (class 1259 OID 173179)
-- Name: colombian_banks; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.colombian_banks (
    id integer NOT NULL,
    bank_code character varying(10) NOT NULL,
    bank_name character varying(100) NOT NULL,
    is_active smallint DEFAULT 1,
    trial551 character(1)
);


ALTER TABLE public.colombian_banks OWNER TO postgres;

--
-- TOC entry 242 (class 1259 OID 173178)
-- Name: colombian_banks_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.colombian_banks_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.colombian_banks_id_seq OWNER TO postgres;

--
-- TOC entry 5679 (class 0 OID 0)
-- Dependencies: 242
-- Name: colombian_banks_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.colombian_banks_id_seq OWNED BY public.colombian_banks.id;


--
-- TOC entry 245 (class 1259 OID 173195)
-- Name: colors; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.colors (
    id integer NOT NULL,
    name character varying(50) NOT NULL,
    hex_code character varying(7),
    is_active smallint DEFAULT 1,
    created_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP,
    trial551 character(1)
);


ALTER TABLE public.colors OWNER TO postgres;

--
-- TOC entry 244 (class 1259 OID 173194)
-- Name: colors_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.colors_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.colors_id_seq OWNER TO postgres;

--
-- TOC entry 5680 (class 0 OID 0)
-- Dependencies: 244
-- Name: colors_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.colors_id_seq OWNED BY public.colors.id;


--
-- TOC entry 247 (class 1259 OID 173212)
-- Name: discount_code_products; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.discount_code_products (
    id integer NOT NULL,
    discount_code_id integer NOT NULL,
    product_id integer NOT NULL,
    created_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP,
    trial551 character(1)
);


ALTER TABLE public.discount_code_products OWNER TO postgres;

--
-- TOC entry 246 (class 1259 OID 173211)
-- Name: discount_code_products_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.discount_code_products_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.discount_code_products_id_seq OWNER TO postgres;

--
-- TOC entry 5681 (class 0 OID 0)
-- Dependencies: 246
-- Name: discount_code_products_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.discount_code_products_id_seq OWNED BY public.discount_code_products.id;


--
-- TOC entry 249 (class 1259 OID 173220)
-- Name: discount_code_usage; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.discount_code_usage (
    id integer NOT NULL,
    discount_code_id integer NOT NULL,
    user_id character varying(20),
    order_id integer,
    used_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP,
    trial551 character(1)
);


ALTER TABLE public.discount_code_usage OWNER TO postgres;

--
-- TOC entry 248 (class 1259 OID 173219)
-- Name: discount_code_usage_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.discount_code_usage_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.discount_code_usage_id_seq OWNER TO postgres;

--
-- TOC entry 5682 (class 0 OID 0)
-- Dependencies: 248
-- Name: discount_code_usage_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.discount_code_usage_id_seq OWNED BY public.discount_code_usage.id;


--
-- TOC entry 251 (class 1259 OID 173228)
-- Name: discount_codes; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.discount_codes (
    id integer NOT NULL,
    code character varying(20) NOT NULL,
    discount_type_id integer NOT NULL,
    discount_value numeric(10,2),
    max_uses integer,
    used_count integer DEFAULT 0,
    start_date timestamp without time zone,
    end_date timestamp without time zone,
    is_active smallint DEFAULT 1,
    is_single_use smallint DEFAULT 0,
    created_by character varying(20) NOT NULL,
    created_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP,
    updated_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP,
    trial551 character(1)
);


ALTER TABLE public.discount_codes OWNER TO postgres;

--
-- TOC entry 250 (class 1259 OID 173227)
-- Name: discount_codes_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.discount_codes_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.discount_codes_id_seq OWNER TO postgres;

--
-- TOC entry 5683 (class 0 OID 0)
-- Dependencies: 250
-- Name: discount_codes_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.discount_codes_id_seq OWNED BY public.discount_codes.id;


--
-- TOC entry 253 (class 1259 OID 173248)
-- Name: discount_types; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.discount_types (
    id integer NOT NULL,
    name character varying(50) NOT NULL,
    description character varying(255),
    is_active smallint DEFAULT 1,
    created_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP,
    updated_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP,
    trial551 character(1)
);


ALTER TABLE public.discount_types OWNER TO postgres;

--
-- TOC entry 252 (class 1259 OID 173247)
-- Name: discount_types_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.discount_types_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.discount_types_id_seq OWNER TO postgres;

--
-- TOC entry 5684 (class 0 OID 0)
-- Dependencies: 252
-- Name: discount_types_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.discount_types_id_seq OWNED BY public.discount_types.id;


--
-- TOC entry 255 (class 1259 OID 173266)
-- Name: eliminaciones_auditoria; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.eliminaciones_auditoria (
    id integer NOT NULL,
    nombre character varying(100) NOT NULL,
    accion character varying(50) DEFAULT 'Eliminado'::character varying NOT NULL,
    fecha_eliminacion timestamp without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL,
    trial551 character(1)
);


ALTER TABLE public.eliminaciones_auditoria OWNER TO postgres;

--
-- TOC entry 254 (class 1259 OID 173265)
-- Name: eliminaciones_auditoria_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.eliminaciones_auditoria_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.eliminaciones_auditoria_id_seq OWNER TO postgres;

--
-- TOC entry 5685 (class 0 OID 0)
-- Dependencies: 254
-- Name: eliminaciones_auditoria_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.eliminaciones_auditoria_id_seq OWNED BY public.eliminaciones_auditoria.id;


--
-- TOC entry 257 (class 1259 OID 173275)
-- Name: failed_jobs; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.failed_jobs (
    id bigint NOT NULL,
    uuid character varying(255) NOT NULL,
    connection text NOT NULL,
    queue text NOT NULL,
    payload text NOT NULL,
    exception text NOT NULL,
    failed_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL,
    trial551 character(1)
);


ALTER TABLE public.failed_jobs OWNER TO postgres;

--
-- TOC entry 256 (class 1259 OID 173274)
-- Name: failed_jobs_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.failed_jobs_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.failed_jobs_id_seq OWNER TO postgres;

--
-- TOC entry 5686 (class 0 OID 0)
-- Dependencies: 256
-- Name: failed_jobs_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.failed_jobs_id_seq OWNED BY public.failed_jobs.id;


--
-- TOC entry 259 (class 1259 OID 173286)
-- Name: fixed_amount_discounts; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.fixed_amount_discounts (
    id integer NOT NULL,
    discount_code_id integer NOT NULL,
    amount numeric(10,2) NOT NULL,
    min_order_amount numeric(10,2),
    trial551 character(1)
);


ALTER TABLE public.fixed_amount_discounts OWNER TO postgres;

--
-- TOC entry 258 (class 1259 OID 173285)
-- Name: fixed_amount_discounts_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.fixed_amount_discounts_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.fixed_amount_discounts_id_seq OWNER TO postgres;

--
-- TOC entry 5687 (class 0 OID 0)
-- Dependencies: 258
-- Name: fixed_amount_discounts_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.fixed_amount_discounts_id_seq OWNED BY public.fixed_amount_discounts.id;


--
-- TOC entry 261 (class 1259 OID 173293)
-- Name: free_shipping_discounts; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.free_shipping_discounts (
    id integer NOT NULL,
    discount_code_id integer NOT NULL,
    shipping_method_id integer,
    trial551 character(1)
);


ALTER TABLE public.free_shipping_discounts OWNER TO postgres;

--
-- TOC entry 260 (class 1259 OID 173292)
-- Name: free_shipping_discounts_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.free_shipping_discounts_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.free_shipping_discounts_id_seq OWNER TO postgres;

--
-- TOC entry 5688 (class 0 OID 0)
-- Dependencies: 260
-- Name: free_shipping_discounts_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.free_shipping_discounts_id_seq OWNED BY public.free_shipping_discounts.id;


--
-- TOC entry 263 (class 1259 OID 173300)
-- Name: google_auth; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.google_auth (
    id integer NOT NULL,
    user_id character varying(20) NOT NULL,
    google_id character varying(255) NOT NULL,
    access_token character varying(255) NOT NULL,
    created_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP,
    trial551 character(1)
);


ALTER TABLE public.google_auth OWNER TO postgres;

--
-- TOC entry 262 (class 1259 OID 173299)
-- Name: google_auth_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.google_auth_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.google_auth_id_seq OWNER TO postgres;

--
-- TOC entry 5689 (class 0 OID 0)
-- Dependencies: 262
-- Name: google_auth_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.google_auth_id_seq OWNED BY public.google_auth.id;


--
-- TOC entry 264 (class 1259 OID 173321)
-- Name: job_batches; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.job_batches (
    id character varying(255) NOT NULL,
    name character varying(255) NOT NULL,
    total_jobs integer NOT NULL,
    pending_jobs integer NOT NULL,
    failed_jobs integer NOT NULL,
    failed_job_ids text NOT NULL,
    options text,
    cancelled_at integer,
    created_at integer NOT NULL,
    finished_at integer,
    trial551 character(1)
);


ALTER TABLE public.job_batches OWNER TO postgres;

--
-- TOC entry 266 (class 1259 OID 173329)
-- Name: jobs; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.jobs (
    id bigint NOT NULL,
    queue character varying(255) NOT NULL,
    payload text NOT NULL,
    attempts smallint NOT NULL,
    reserved_at bigint,
    available_at bigint NOT NULL,
    created_at bigint NOT NULL,
    trial551 character(1)
);


ALTER TABLE public.jobs OWNER TO postgres;

--
-- TOC entry 265 (class 1259 OID 173328)
-- Name: jobs_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.jobs_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.jobs_id_seq OWNER TO postgres;

--
-- TOC entry 5690 (class 0 OID 0)
-- Dependencies: 265
-- Name: jobs_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.jobs_id_seq OWNED BY public.jobs.id;


--
-- TOC entry 268 (class 1259 OID 173339)
-- Name: login_attempts; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.login_attempts (
    id integer NOT NULL,
    username character varying(255) NOT NULL,
    ip_address character varying(45) NOT NULL,
    attempt_date timestamp without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL,
    trial551 character(1)
);


ALTER TABLE public.login_attempts OWNER TO postgres;

--
-- TOC entry 267 (class 1259 OID 173338)
-- Name: login_attempts_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.login_attempts_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.login_attempts_id_seq OWNER TO postgres;

--
-- TOC entry 5691 (class 0 OID 0)
-- Dependencies: 267
-- Name: login_attempts_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.login_attempts_id_seq OWNED BY public.login_attempts.id;


--
-- TOC entry 270 (class 1259 OID 173355)
-- Name: migrations; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.migrations (
    id integer NOT NULL,
    migration character varying(255) NOT NULL,
    batch integer NOT NULL,
    trial551 character(1)
);


ALTER TABLE public.migrations OWNER TO postgres;

--
-- TOC entry 269 (class 1259 OID 173354)
-- Name: migrations_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.migrations_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.migrations_id_seq OWNER TO postgres;

--
-- TOC entry 5692 (class 0 OID 0)
-- Dependencies: 269
-- Name: migrations_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.migrations_id_seq OWNED BY public.migrations.id;


--
-- TOC entry 272 (class 1259 OID 173370)
-- Name: notification_preferences; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.notification_preferences (
    id integer NOT NULL,
    user_id character varying(20) NOT NULL,
    type_id integer NOT NULL,
    email_enabled smallint DEFAULT 1,
    sms_enabled smallint DEFAULT 0,
    push_enabled smallint DEFAULT 1,
    created_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP,
    updated_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP,
    trial551 character(1)
);


ALTER TABLE public.notification_preferences OWNER TO postgres;

--
-- TOC entry 271 (class 1259 OID 173369)
-- Name: notification_preferences_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.notification_preferences_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.notification_preferences_id_seq OWNER TO postgres;

--
-- TOC entry 5693 (class 0 OID 0)
-- Dependencies: 271
-- Name: notification_preferences_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.notification_preferences_id_seq OWNED BY public.notification_preferences.id;


--
-- TOC entry 274 (class 1259 OID 173390)
-- Name: notification_queue; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.notification_queue (
    id integer NOT NULL,
    notification_id integer NOT NULL,
    channel character varying(5) NOT NULL,
    status character varying(10) DEFAULT 'pending'::character varying,
    attempts smallint DEFAULT 0,
    last_attempt_at timestamp without time zone,
    scheduled_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP,
    sent_at timestamp without time zone,
    error_message text,
    trial551 character(1)
);


ALTER TABLE public.notification_queue OWNER TO postgres;

--
-- TOC entry 273 (class 1259 OID 173389)
-- Name: notification_queue_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.notification_queue_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.notification_queue_id_seq OWNER TO postgres;

--
-- TOC entry 5694 (class 0 OID 0)
-- Dependencies: 273
-- Name: notification_queue_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.notification_queue_id_seq OWNED BY public.notification_queue.id;


--
-- TOC entry 276 (class 1259 OID 173402)
-- Name: notification_types; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.notification_types (
    id integer NOT NULL,
    name character varying(50) NOT NULL,
    description character varying(255),
    template text,
    is_active smallint DEFAULT 1,
    created_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP,
    updated_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP,
    trial551 character(1)
);


ALTER TABLE public.notification_types OWNER TO postgres;

--
-- TOC entry 275 (class 1259 OID 173401)
-- Name: notification_types_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.notification_types_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.notification_types_id_seq OWNER TO postgres;

--
-- TOC entry 5695 (class 0 OID 0)
-- Dependencies: 275
-- Name: notification_types_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.notification_types_id_seq OWNED BY public.notification_types.id;


--
-- TOC entry 278 (class 1259 OID 173426)
-- Name: notifications; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.notifications (
    id integer NOT NULL,
    user_id character varying(20) NOT NULL,
    type_id integer NOT NULL,
    title character varying(100) NOT NULL,
    message text NOT NULL,
    related_entity_type character varying(9),
    related_entity_id integer,
    is_read smallint DEFAULT 0,
    is_email_sent smallint DEFAULT 0,
    is_sms_sent smallint DEFAULT 0,
    is_push_sent smallint DEFAULT 0,
    expires_at timestamp without time zone,
    created_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP,
    read_at timestamp without time zone,
    trial551 character(1)
);


ALTER TABLE public.notifications OWNER TO postgres;

--
-- TOC entry 277 (class 1259 OID 173425)
-- Name: notifications_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.notifications_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.notifications_id_seq OWNER TO postgres;

--
-- TOC entry 5696 (class 0 OID 0)
-- Dependencies: 277
-- Name: notifications_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.notifications_id_seq OWNED BY public.notifications.id;


--
-- TOC entry 280 (class 1259 OID 173452)
-- Name: order_items; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.order_items (
    id integer NOT NULL,
    order_id integer NOT NULL,
    product_id integer NOT NULL,
    color_variant_id integer,
    size_variant_id integer,
    product_name character varying(255) NOT NULL,
    variant_name character varying(255),
    price numeric(10,2) NOT NULL,
    quantity integer NOT NULL,
    total numeric(10,2) NOT NULL,
    created_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP,
    trial551 character(1)
);


ALTER TABLE public.order_items OWNER TO postgres;

--
-- TOC entry 279 (class 1259 OID 173451)
-- Name: order_items_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.order_items_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.order_items_id_seq OWNER TO postgres;

--
-- TOC entry 5697 (class 0 OID 0)
-- Dependencies: 279
-- Name: order_items_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.order_items_id_seq OWNED BY public.order_items.id;


--
-- TOC entry 282 (class 1259 OID 173474)
-- Name: order_status_history; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.order_status_history (
    id integer NOT NULL,
    order_id integer NOT NULL,
    changed_by character varying(20),
    changed_by_name character varying(100),
    change_type character varying(14) DEFAULT 'other'::character varying NOT NULL,
    field_changed character varying(100),
    old_value text,
    new_value text,
    description text NOT NULL,
    ip_address character varying(45),
    user_agent text,
    created_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP,
    trial551 character(1)
);


ALTER TABLE public.order_status_history OWNER TO postgres;

--
-- TOC entry 281 (class 1259 OID 173473)
-- Name: order_status_history_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.order_status_history_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.order_status_history_id_seq OWNER TO postgres;

--
-- TOC entry 5698 (class 0 OID 0)
-- Dependencies: 281
-- Name: order_status_history_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.order_status_history_id_seq OWNED BY public.order_status_history.id;


--
-- TOC entry 284 (class 1259 OID 173497)
-- Name: order_views; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.order_views (
    id integer NOT NULL,
    order_id integer NOT NULL,
    user_id character varying(20) NOT NULL,
    viewed_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP,
    trial551 character(1)
);


ALTER TABLE public.order_views OWNER TO postgres;

--
-- TOC entry 283 (class 1259 OID 173496)
-- Name: order_views_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.order_views_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.order_views_id_seq OWNER TO postgres;

--
-- TOC entry 5699 (class 0 OID 0)
-- Dependencies: 283
-- Name: order_views_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.order_views_id_seq OWNED BY public.order_views.id;


--
-- TOC entry 288 (class 1259 OID 173542)
-- Name: orders; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.orders (
    id integer NOT NULL,
    order_number character varying(20) NOT NULL,
    invoice_number character varying(20),
    user_id character varying(20),
    status character varying(10) DEFAULT 'pending'::character varying,
    subtotal numeric(10,2) NOT NULL,
    shipping_cost numeric(10,2) DEFAULT 0.00,
    discount_amount numeric(10,2) DEFAULT 0.00 NOT NULL,
    total numeric(10,2) NOT NULL,
    payment_method character varying(50),
    payment_status character varying(8) DEFAULT 'pending'::character varying,
    shipping_address text,
    shipping_city character varying(100),
    shipping_method_id integer,
    shipping_address_id integer,
    billing_address text,
    billing_address_id integer,
    notes text,
    created_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP,
    updated_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP,
    invoice_resolution character varying(50),
    invoice_date timestamp without time zone,
    trial554 character(1)
);


ALTER TABLE public.orders OWNER TO postgres;

--
-- TOC entry 287 (class 1259 OID 173541)
-- Name: orders_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.orders_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.orders_id_seq OWNER TO postgres;

--
-- TOC entry 5700 (class 0 OID 0)
-- Dependencies: 287
-- Name: orders_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.orders_id_seq OWNED BY public.orders.id;


--
-- TOC entry 290 (class 1259 OID 173570)
-- Name: password_resets; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.password_resets (
    id integer NOT NULL,
    user_id character varying(20) NOT NULL,
    token character varying(255) NOT NULL,
    expires_at timestamp without time zone NOT NULL,
    is_used smallint DEFAULT 0,
    created_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP,
    trial554 character(1)
);


ALTER TABLE public.password_resets OWNER TO postgres;

--
-- TOC entry 289 (class 1259 OID 173569)
-- Name: password_resets_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.password_resets_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.password_resets_id_seq OWNER TO postgres;

--
-- TOC entry 5701 (class 0 OID 0)
-- Dependencies: 289
-- Name: password_resets_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.password_resets_id_seq OWNED BY public.password_resets.id;


--
-- TOC entry 292 (class 1259 OID 173587)
-- Name: payment_transactions; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.payment_transactions (
    id integer NOT NULL,
    order_id integer,
    user_id character varying(20),
    amount numeric(10,2) NOT NULL,
    reference_number character varying(50),
    payment_proof character varying(255),
    status character varying(8) DEFAULT 'pending'::character varying,
    admin_notes text,
    verified_by character varying(20),
    verified_at timestamp without time zone,
    created_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP,
    updated_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP,
    trial554 character(1)
);


ALTER TABLE public.payment_transactions OWNER TO postgres;

--
-- TOC entry 291 (class 1259 OID 173586)
-- Name: payment_transactions_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.payment_transactions_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.payment_transactions_id_seq OWNER TO postgres;

--
-- TOC entry 5702 (class 0 OID 0)
-- Dependencies: 291
-- Name: payment_transactions_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.payment_transactions_id_seq OWNED BY public.payment_transactions.id;


--
-- TOC entry 294 (class 1259 OID 173611)
-- Name: percentage_discounts; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.percentage_discounts (
    id integer NOT NULL,
    discount_code_id integer NOT NULL,
    percentage numeric(5,2) NOT NULL,
    max_discount_amount numeric(10,2),
    trial554 character(1)
);


ALTER TABLE public.percentage_discounts OWNER TO postgres;

--
-- TOC entry 293 (class 1259 OID 173610)
-- Name: percentage_discounts_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.percentage_discounts_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.percentage_discounts_id_seq OWNER TO postgres;

--
-- TOC entry 5703 (class 0 OID 0)
-- Dependencies: 293
-- Name: percentage_discounts_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.percentage_discounts_id_seq OWNED BY public.percentage_discounts.id;


--
-- TOC entry 296 (class 1259 OID 173626)
-- Name: personal_access_tokens; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.personal_access_tokens (
    id bigint NOT NULL,
    tokenable_type character varying(255) NOT NULL,
    tokenable_id bigint NOT NULL,
    name character varying(255) NOT NULL,
    token character varying(64) NOT NULL,
    abilities text,
    last_used_at timestamp without time zone,
    expires_at timestamp without time zone,
    created_at timestamp without time zone,
    updated_at timestamp without time zone,
    trial554 character(1)
);


ALTER TABLE public.personal_access_tokens OWNER TO postgres;

--
-- TOC entry 295 (class 1259 OID 173625)
-- Name: personal_access_tokens_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.personal_access_tokens_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.personal_access_tokens_id_seq OWNER TO postgres;

--
-- TOC entry 5704 (class 0 OID 0)
-- Dependencies: 295
-- Name: personal_access_tokens_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.personal_access_tokens_id_seq OWNED BY public.personal_access_tokens.id;


--
-- TOC entry 298 (class 1259 OID 173637)
-- Name: popular_searches; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.popular_searches (
    id integer NOT NULL,
    search_term character varying(255) NOT NULL,
    search_count integer DEFAULT 1 NOT NULL,
    last_searched timestamp without time zone DEFAULT CURRENT_TIMESTAMP,
    trial554 character(1)
);


ALTER TABLE public.popular_searches OWNER TO postgres;

--
-- TOC entry 297 (class 1259 OID 173636)
-- Name: popular_searches_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.popular_searches_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.popular_searches_id_seq OWNER TO postgres;

--
-- TOC entry 5705 (class 0 OID 0)
-- Dependencies: 297
-- Name: popular_searches_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.popular_searches_id_seq OWNED BY public.popular_searches.id;


--
-- TOC entry 300 (class 1259 OID 173654)
-- Name: product_collections; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.product_collections (
    id integer NOT NULL,
    product_id integer NOT NULL,
    collection_id integer NOT NULL,
    display_order integer DEFAULT 0,
    created_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP,
    trial554 character(1)
);


ALTER TABLE public.product_collections OWNER TO postgres;

--
-- TOC entry 299 (class 1259 OID 173653)
-- Name: product_collections_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.product_collections_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.product_collections_id_seq OWNER TO postgres;

--
-- TOC entry 5706 (class 0 OID 0)
-- Dependencies: 299
-- Name: product_collections_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.product_collections_id_seq OWNED BY public.product_collections.id;


--
-- TOC entry 302 (class 1259 OID 173663)
-- Name: product_color_variants; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.product_color_variants (
    id integer NOT NULL,
    product_id integer NOT NULL,
    color_id integer,
    is_default smallint DEFAULT 0,
    trial554 character(1)
);


ALTER TABLE public.product_color_variants OWNER TO postgres;

--
-- TOC entry 301 (class 1259 OID 173662)
-- Name: product_color_variants_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.product_color_variants_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.product_color_variants_id_seq OWNER TO postgres;

--
-- TOC entry 5707 (class 0 OID 0)
-- Dependencies: 301
-- Name: product_color_variants_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.product_color_variants_id_seq OWNED BY public.product_color_variants.id;


--
-- TOC entry 304 (class 1259 OID 173679)
-- Name: product_images; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.product_images (
    id integer NOT NULL,
    product_id integer NOT NULL,
    color_variant_id integer,
    image_path character varying(255) NOT NULL,
    alt_text character varying(255),
    "order" integer DEFAULT 0,
    created_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP,
    is_primary smallint DEFAULT 0,
    trial554 character(1)
);


ALTER TABLE public.product_images OWNER TO postgres;

--
-- TOC entry 303 (class 1259 OID 173678)
-- Name: product_images_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.product_images_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.product_images_id_seq OWNER TO postgres;

--
-- TOC entry 5708 (class 0 OID 0)
-- Dependencies: 303
-- Name: product_images_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.product_images_id_seq OWNED BY public.product_images.id;


--
-- TOC entry 306 (class 1259 OID 173703)
-- Name: product_questions; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.product_questions (
    id integer NOT NULL,
    product_id integer NOT NULL,
    user_id character varying(20) NOT NULL,
    question text NOT NULL,
    created_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP,
    trial554 character(1)
);


ALTER TABLE public.product_questions OWNER TO postgres;

--
-- TOC entry 305 (class 1259 OID 173702)
-- Name: product_questions_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.product_questions_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.product_questions_id_seq OWNER TO postgres;

--
-- TOC entry 5709 (class 0 OID 0)
-- Dependencies: 305
-- Name: product_questions_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.product_questions_id_seq OWNED BY public.product_questions.id;


--
-- TOC entry 308 (class 1259 OID 173725)
-- Name: product_reviews; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.product_reviews (
    id integer NOT NULL,
    product_id integer NOT NULL,
    user_id character varying(20) NOT NULL,
    order_id integer,
    rating smallint NOT NULL,
    title character varying(100) NOT NULL,
    comment text NOT NULL,
    images text,
    is_verified smallint DEFAULT 0,
    is_approved smallint DEFAULT 1,
    created_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP,
    updated_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP,
    trial554 character(1)
);


ALTER TABLE public.product_reviews OWNER TO postgres;

--
-- TOC entry 307 (class 1259 OID 173724)
-- Name: product_reviews_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.product_reviews_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.product_reviews_id_seq OWNER TO postgres;

--
-- TOC entry 5710 (class 0 OID 0)
-- Dependencies: 307
-- Name: product_reviews_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.product_reviews_id_seq OWNED BY public.product_reviews.id;


--
-- TOC entry 310 (class 1259 OID 173750)
-- Name: product_size_variants; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.product_size_variants (
    id integer NOT NULL,
    color_variant_id integer NOT NULL,
    size_id integer,
    sku character varying(50),
    barcode character varying(50),
    price numeric(10,2) NOT NULL,
    compare_price numeric(10,2),
    quantity integer DEFAULT 0 NOT NULL,
    is_active smallint DEFAULT 1,
    trial554 character(1)
);


ALTER TABLE public.product_size_variants OWNER TO postgres;

--
-- TOC entry 309 (class 1259 OID 173749)
-- Name: product_size_variants_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.product_size_variants_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.product_size_variants_id_seq OWNER TO postgres;

--
-- TOC entry 5711 (class 0 OID 0)
-- Dependencies: 309
-- Name: product_size_variants_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.product_size_variants_id_seq OWNED BY public.product_size_variants.id;


--
-- TOC entry 312 (class 1259 OID 173767)
-- Name: productos_auditoria; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.productos_auditoria (
    id integer NOT NULL,
    nombre character varying(100) NOT NULL,
    accion character varying(50) DEFAULT 'Creado'::character varying NOT NULL,
    created_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL,
    updated_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL,
    trial554 character(1)
);


ALTER TABLE public.productos_auditoria OWNER TO postgres;

--
-- TOC entry 311 (class 1259 OID 173766)
-- Name: productos_auditoria_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.productos_auditoria_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.productos_auditoria_id_seq OWNER TO postgres;

--
-- TOC entry 5712 (class 0 OID 0)
-- Dependencies: 311
-- Name: productos_auditoria_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.productos_auditoria_id_seq OWNED BY public.productos_auditoria.id;


--
-- TOC entry 314 (class 1259 OID 173785)
-- Name: products; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.products (
    id integer NOT NULL,
    name character varying(255) NOT NULL,
    slug character varying(255) NOT NULL,
    description text,
    brand character varying(100),
    gender character varying(6) DEFAULT 'unisex'::character varying NOT NULL,
    collection character varying(50),
    material character varying(100),
    care_instructions text,
    compare_price numeric(10,2),
    price numeric(10,2),
    category_id integer NOT NULL,
    collection_id integer,
    is_featured smallint DEFAULT 0,
    is_active smallint DEFAULT 1,
    created_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP,
    updated_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP,
    trial554 character(1)
);


ALTER TABLE public.products OWNER TO postgres;

--
-- TOC entry 313 (class 1259 OID 173784)
-- Name: products_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.products_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.products_id_seq OWNER TO postgres;

--
-- TOC entry 5713 (class 0 OID 0)
-- Dependencies: 313
-- Name: products_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.products_id_seq OWNED BY public.products.id;


--
-- TOC entry 316 (class 1259 OID 173811)
-- Name: question_answers; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.question_answers (
    id integer NOT NULL,
    question_id integer NOT NULL,
    user_id character varying(20) NOT NULL,
    answer text NOT NULL,
    is_seller smallint DEFAULT 0,
    created_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP,
    trial554 character(1)
);


ALTER TABLE public.question_answers OWNER TO postgres;

--
-- TOC entry 315 (class 1259 OID 173810)
-- Name: question_answers_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.question_answers_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.question_answers_id_seq OWNER TO postgres;

--
-- TOC entry 5714 (class 0 OID 0)
-- Dependencies: 315
-- Name: question_answers_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.question_answers_id_seq OWNED BY public.question_answers.id;


--
-- TOC entry 318 (class 1259 OID 173834)
-- Name: review_votes; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.review_votes (
    id integer NOT NULL,
    review_id integer NOT NULL,
    user_id character varying(20) NOT NULL,
    is_helpful smallint NOT NULL,
    created_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP,
    trial554 character(1)
);


ALTER TABLE public.review_votes OWNER TO postgres;

--
-- TOC entry 317 (class 1259 OID 173833)
-- Name: review_votes_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.review_votes_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.review_votes_id_seq OWNER TO postgres;

--
-- TOC entry 5715 (class 0 OID 0)
-- Dependencies: 317
-- Name: review_votes_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.review_votes_id_seq OWNED BY public.review_votes.id;


--
-- TOC entry 320 (class 1259 OID 173850)
-- Name: search_history; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.search_history (
    id integer NOT NULL,
    user_id character varying(20),
    search_term character varying(255) NOT NULL,
    created_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP,
    trial554 character(1)
);


ALTER TABLE public.search_history OWNER TO postgres;

--
-- TOC entry 319 (class 1259 OID 173849)
-- Name: search_history_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.search_history_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.search_history_id_seq OWNER TO postgres;

--
-- TOC entry 5716 (class 0 OID 0)
-- Dependencies: 319
-- Name: search_history_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.search_history_id_seq OWNED BY public.search_history.id;


--
-- TOC entry 321 (class 1259 OID 173865)
-- Name: sessions; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.sessions (
    id character varying(255) NOT NULL,
    user_id character varying(20),
    ip_address character varying(45),
    user_agent text,
    payload text NOT NULL,
    last_activity integer NOT NULL,
    trial554 character(1)
);


ALTER TABLE public.sessions OWNER TO postgres;

--
-- TOC entry 286 (class 1259 OID 173513)
-- Name: shipping_methods; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.shipping_methods (
    id integer NOT NULL,
    name character varying(100) NOT NULL,
    description text,
    base_cost numeric(10,2) DEFAULT 0.00 NOT NULL,
    delivery_time character varying(50),
    is_active smallint DEFAULT 1,
    created_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP,
    updated_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP,
    free_shipping_threshold numeric(10,2),
    available_cities text,
    estimated_days_min integer DEFAULT 1,
    estimated_days_max integer DEFAULT 3,
    city character varying(100) DEFAULT 'Medellín'::character varying,
    free_shipping_minimum numeric(10,2),
    icon character varying(50) DEFAULT 'fas fa-truck'::character varying,
    trial554 character(1)
);


ALTER TABLE public.shipping_methods OWNER TO postgres;

--
-- TOC entry 285 (class 1259 OID 173512)
-- Name: shipping_methods_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.shipping_methods_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.shipping_methods_id_seq OWNER TO postgres;

--
-- TOC entry 5717 (class 0 OID 0)
-- Dependencies: 285
-- Name: shipping_methods_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.shipping_methods_id_seq OWNED BY public.shipping_methods.id;


--
-- TOC entry 323 (class 1259 OID 173885)
-- Name: shipping_price_rules; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.shipping_price_rules (
    id integer NOT NULL,
    min_price numeric(10,2) NOT NULL,
    max_price numeric(10,2),
    shipping_cost numeric(10,2) NOT NULL,
    is_active smallint DEFAULT 1,
    created_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP,
    updated_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP,
    trial554 character(1)
);


ALTER TABLE public.shipping_price_rules OWNER TO postgres;

--
-- TOC entry 322 (class 1259 OID 173884)
-- Name: shipping_price_rules_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.shipping_price_rules_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.shipping_price_rules_id_seq OWNER TO postgres;

--
-- TOC entry 5718 (class 0 OID 0)
-- Dependencies: 322
-- Name: shipping_price_rules_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.shipping_price_rules_id_seq OWNED BY public.shipping_price_rules.id;


--
-- TOC entry 325 (class 1259 OID 173903)
-- Name: site_settings; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.site_settings (
    id integer NOT NULL,
    setting_key character varying(120) NOT NULL,
    setting_value text,
    category character varying(40) DEFAULT 'general'::character varying,
    updated_by character varying(64),
    updated_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP,
    trial554 character(1)
);


ALTER TABLE public.site_settings OWNER TO postgres;

--
-- TOC entry 324 (class 1259 OID 173902)
-- Name: site_settings_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.site_settings_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.site_settings_id_seq OWNER TO postgres;

--
-- TOC entry 5719 (class 0 OID 0)
-- Dependencies: 324
-- Name: site_settings_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.site_settings_id_seq OWNED BY public.site_settings.id;


--
-- TOC entry 327 (class 1259 OID 173927)
-- Name: sizes; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.sizes (
    id integer NOT NULL,
    name character varying(50) NOT NULL,
    description character varying(100),
    is_active smallint DEFAULT 1,
    created_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP,
    trial554 character(1)
);


ALTER TABLE public.sizes OWNER TO postgres;

--
-- TOC entry 326 (class 1259 OID 173926)
-- Name: sizes_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.sizes_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.sizes_id_seq OWNER TO postgres;

--
-- TOC entry 5720 (class 0 OID 0)
-- Dependencies: 326
-- Name: sizes_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.sizes_id_seq OWNED BY public.sizes.id;


--
-- TOC entry 329 (class 1259 OID 173944)
-- Name: sliders; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.sliders (
    id integer NOT NULL,
    title character varying(255) NOT NULL,
    subtitle character varying(255),
    image character varying(500) NOT NULL,
    link character varying(500),
    order_position integer DEFAULT 0 NOT NULL,
    is_active smallint DEFAULT 1 NOT NULL,
    created_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP,
    updated_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP,
    trial558 character(1)
);


ALTER TABLE public.sliders OWNER TO postgres;

--
-- TOC entry 328 (class 1259 OID 173943)
-- Name: sliders_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.sliders_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.sliders_id_seq OWNER TO postgres;

--
-- TOC entry 5721 (class 0 OID 0)
-- Dependencies: 328
-- Name: sliders_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.sliders_id_seq OWNED BY public.sliders.id;


--
-- TOC entry 331 (class 1259 OID 173969)
-- Name: stock_history; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.stock_history (
    id integer NOT NULL,
    variant_id integer NOT NULL,
    user_id character varying(20) NOT NULL,
    previous_qty integer NOT NULL,
    new_qty integer NOT NULL,
    operation character varying(12) NOT NULL,
    notes text,
    created_at timestamp without time zone NOT NULL,
    trial558 character(1)
);


ALTER TABLE public.stock_history OWNER TO postgres;

--
-- TOC entry 330 (class 1259 OID 173968)
-- Name: stock_history_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.stock_history_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.stock_history_id_seq OWNER TO postgres;

--
-- TOC entry 5722 (class 0 OID 0)
-- Dependencies: 330
-- Name: stock_history_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.stock_history_id_seq OWNED BY public.stock_history.id;


--
-- TOC entry 333 (class 1259 OID 173978)
-- Name: user_addresses; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.user_addresses (
    id integer NOT NULL,
    user_id character varying(20) NOT NULL,
    address_type character varying(11) DEFAULT 'casa'::character varying NOT NULL,
    alias character varying(50) NOT NULL,
    recipient_name character varying(100) NOT NULL,
    recipient_phone character varying(15) NOT NULL,
    address character varying(255) NOT NULL,
    complement character varying(100),
    neighborhood character varying(100) NOT NULL,
    building_type character varying(11) DEFAULT 'casa'::character varying NOT NULL,
    building_name character varying(100),
    apartment_number character varying(20),
    delivery_instructions text,
    is_default smallint DEFAULT 0,
    is_active smallint DEFAULT 1,
    created_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP,
    updated_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP,
    gps_latitude numeric(10,8),
    gps_longitude numeric(11,8),
    gps_accuracy numeric(10,2),
    gps_timestamp timestamp without time zone,
    gps_used smallint DEFAULT 0,
    trial558 character(1)
);


ALTER TABLE public.user_addresses OWNER TO postgres;

--
-- TOC entry 332 (class 1259 OID 173977)
-- Name: user_addresses_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.user_addresses_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.user_addresses_id_seq OWNER TO postgres;

--
-- TOC entry 5723 (class 0 OID 0)
-- Dependencies: 332
-- Name: user_addresses_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.user_addresses_id_seq OWNED BY public.user_addresses.id;


--
-- TOC entry 335 (class 1259 OID 174006)
-- Name: user_applied_discounts; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.user_applied_discounts (
    id integer NOT NULL,
    user_id character varying(20) NOT NULL,
    discount_code_id integer NOT NULL,
    discount_code character varying(20) NOT NULL,
    discount_amount numeric(10,2) NOT NULL,
    applied_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP,
    expires_at timestamp without time zone NOT NULL,
    is_used smallint DEFAULT 0,
    used_at timestamp without time zone,
    trial558 character(1)
);


ALTER TABLE public.user_applied_discounts OWNER TO postgres;

--
-- TOC entry 334 (class 1259 OID 174005)
-- Name: user_applied_discounts_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.user_applied_discounts_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.user_applied_discounts_id_seq OWNER TO postgres;

--
-- TOC entry 5724 (class 0 OID 0)
-- Dependencies: 334
-- Name: user_applied_discounts_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.user_applied_discounts_id_seq OWNED BY public.user_applied_discounts.id;


--
-- TOC entry 218 (class 1259 OID 172924)
-- Name: users; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.users (
    id character varying(20) NOT NULL,
    name character varying(100) NOT NULL,
    email character varying(100) NOT NULL,
    phone character varying(15),
    password character varying(255),
    image character varying(255),
    role character varying(8) DEFAULT 'customer'::character varying,
    is_blocked smallint DEFAULT 0,
    created_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP,
    updated_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP,
    last_access timestamp without time zone,
    remember_token character varying(255),
    token_expiry timestamp without time zone,
    trial548 character(1)
);


ALTER TABLE public.users OWNER TO postgres;

--
-- TOC entry 337 (class 1259 OID 174023)
-- Name: variant_images; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.variant_images (
    id integer NOT NULL,
    color_variant_id integer NOT NULL,
    product_id integer NOT NULL,
    image_id integer,
    image_path character varying(255) NOT NULL,
    alt_text character varying(255),
    "order" integer DEFAULT 0,
    is_primary smallint DEFAULT 0,
    created_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP,
    trial558 character(1)
);


ALTER TABLE public.variant_images OWNER TO postgres;

--
-- TOC entry 336 (class 1259 OID 174022)
-- Name: variant_images_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.variant_images_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.variant_images_id_seq OWNER TO postgres;

--
-- TOC entry 5725 (class 0 OID 0)
-- Dependencies: 336
-- Name: variant_images_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.variant_images_id_seq OWNED BY public.variant_images.id;


--
-- TOC entry 339 (class 1259 OID 174047)
-- Name: wishlist; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.wishlist (
    id integer NOT NULL,
    user_id character varying(20) NOT NULL,
    product_id integer NOT NULL,
    created_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP,
    trial558 character(1)
);


ALTER TABLE public.wishlist OWNER TO postgres;

--
-- TOC entry 338 (class 1259 OID 174046)
-- Name: wishlist_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.wishlist_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.wishlist_id_seq OWNER TO postgres;

--
-- TOC entry 5726 (class 0 OID 0)
-- Dependencies: 338
-- Name: wishlist_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.wishlist_id_seq OWNED BY public.wishlist.id;


--
-- TOC entry 5061 (class 2604 OID 172949)
-- Name: admin_notification_dismissals id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.admin_notification_dismissals ALTER COLUMN id SET DEFAULT nextval('public.admin_notification_dismissals_id_seq'::regclass);


--
-- TOC entry 5063 (class 2604 OID 172967)
-- Name: announcements id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.announcements ALTER COLUMN id SET DEFAULT nextval('public.announcements_id_seq'::regclass);


--
-- TOC entry 5071 (class 2604 OID 173004)
-- Name: audit_orders id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.audit_orders ALTER COLUMN id SET DEFAULT nextval('public.audit_orders_id_seq'::regclass);


--
-- TOC entry 5073 (class 2604 OID 173026)
-- Name: audit_users id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.audit_users ALTER COLUMN id SET DEFAULT nextval('public.audit_users_id_seq'::regclass);


--
-- TOC entry 5075 (class 2604 OID 173048)
-- Name: bank_account_config id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.bank_account_config ALTER COLUMN id SET DEFAULT nextval('public.bank_account_config_id_seq'::regclass);


--
-- TOC entry 5080 (class 2604 OID 173067)
-- Name: bulk_discount_rules id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.bulk_discount_rules ALTER COLUMN id SET DEFAULT nextval('public.bulk_discount_rules_id_seq'::regclass);


--
-- TOC entry 5084 (class 2604 OID 173099)
-- Name: cart_items id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.cart_items ALTER COLUMN id SET DEFAULT nextval('public.cart_items_id_seq'::regclass);


--
-- TOC entry 5088 (class 2604 OID 173117)
-- Name: carts id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.carts ALTER COLUMN id SET DEFAULT nextval('public.carts_id_seq'::regclass);


--
-- TOC entry 5091 (class 2604 OID 173134)
-- Name: categories id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.categories ALTER COLUMN id SET DEFAULT nextval('public.categories_id_seq'::regclass);


--
-- TOC entry 5095 (class 2604 OID 173158)
-- Name: collections id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.collections ALTER COLUMN id SET DEFAULT nextval('public.collections_id_seq'::regclass);


--
-- TOC entry 5099 (class 2604 OID 173182)
-- Name: colombian_banks id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.colombian_banks ALTER COLUMN id SET DEFAULT nextval('public.colombian_banks_id_seq'::regclass);


--
-- TOC entry 5101 (class 2604 OID 173198)
-- Name: colors id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.colors ALTER COLUMN id SET DEFAULT nextval('public.colors_id_seq'::regclass);


--
-- TOC entry 5104 (class 2604 OID 173215)
-- Name: discount_code_products id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.discount_code_products ALTER COLUMN id SET DEFAULT nextval('public.discount_code_products_id_seq'::regclass);


--
-- TOC entry 5106 (class 2604 OID 173223)
-- Name: discount_code_usage id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.discount_code_usage ALTER COLUMN id SET DEFAULT nextval('public.discount_code_usage_id_seq'::regclass);


--
-- TOC entry 5108 (class 2604 OID 173231)
-- Name: discount_codes id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.discount_codes ALTER COLUMN id SET DEFAULT nextval('public.discount_codes_id_seq'::regclass);


--
-- TOC entry 5114 (class 2604 OID 173251)
-- Name: discount_types id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.discount_types ALTER COLUMN id SET DEFAULT nextval('public.discount_types_id_seq'::regclass);


--
-- TOC entry 5118 (class 2604 OID 173269)
-- Name: eliminaciones_auditoria id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.eliminaciones_auditoria ALTER COLUMN id SET DEFAULT nextval('public.eliminaciones_auditoria_id_seq'::regclass);


--
-- TOC entry 5121 (class 2604 OID 173278)
-- Name: failed_jobs id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.failed_jobs ALTER COLUMN id SET DEFAULT nextval('public.failed_jobs_id_seq'::regclass);


--
-- TOC entry 5123 (class 2604 OID 173289)
-- Name: fixed_amount_discounts id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.fixed_amount_discounts ALTER COLUMN id SET DEFAULT nextval('public.fixed_amount_discounts_id_seq'::regclass);


--
-- TOC entry 5124 (class 2604 OID 173296)
-- Name: free_shipping_discounts id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.free_shipping_discounts ALTER COLUMN id SET DEFAULT nextval('public.free_shipping_discounts_id_seq'::regclass);


--
-- TOC entry 5125 (class 2604 OID 173303)
-- Name: google_auth id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.google_auth ALTER COLUMN id SET DEFAULT nextval('public.google_auth_id_seq'::regclass);


--
-- TOC entry 5127 (class 2604 OID 173332)
-- Name: jobs id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.jobs ALTER COLUMN id SET DEFAULT nextval('public.jobs_id_seq'::regclass);


--
-- TOC entry 5128 (class 2604 OID 173342)
-- Name: login_attempts id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.login_attempts ALTER COLUMN id SET DEFAULT nextval('public.login_attempts_id_seq'::regclass);


--
-- TOC entry 5130 (class 2604 OID 173358)
-- Name: migrations id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.migrations ALTER COLUMN id SET DEFAULT nextval('public.migrations_id_seq'::regclass);


--
-- TOC entry 5131 (class 2604 OID 173373)
-- Name: notification_preferences id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.notification_preferences ALTER COLUMN id SET DEFAULT nextval('public.notification_preferences_id_seq'::regclass);


--
-- TOC entry 5137 (class 2604 OID 173393)
-- Name: notification_queue id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.notification_queue ALTER COLUMN id SET DEFAULT nextval('public.notification_queue_id_seq'::regclass);


--
-- TOC entry 5141 (class 2604 OID 173405)
-- Name: notification_types id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.notification_types ALTER COLUMN id SET DEFAULT nextval('public.notification_types_id_seq'::regclass);


--
-- TOC entry 5145 (class 2604 OID 173429)
-- Name: notifications id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.notifications ALTER COLUMN id SET DEFAULT nextval('public.notifications_id_seq'::regclass);


--
-- TOC entry 5151 (class 2604 OID 173455)
-- Name: order_items id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.order_items ALTER COLUMN id SET DEFAULT nextval('public.order_items_id_seq'::regclass);


--
-- TOC entry 5153 (class 2604 OID 173477)
-- Name: order_status_history id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.order_status_history ALTER COLUMN id SET DEFAULT nextval('public.order_status_history_id_seq'::regclass);


--
-- TOC entry 5156 (class 2604 OID 173500)
-- Name: order_views id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.order_views ALTER COLUMN id SET DEFAULT nextval('public.order_views_id_seq'::regclass);


--
-- TOC entry 5167 (class 2604 OID 173545)
-- Name: orders id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.orders ALTER COLUMN id SET DEFAULT nextval('public.orders_id_seq'::regclass);


--
-- TOC entry 5174 (class 2604 OID 173573)
-- Name: password_resets id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.password_resets ALTER COLUMN id SET DEFAULT nextval('public.password_resets_id_seq'::regclass);


--
-- TOC entry 5177 (class 2604 OID 173590)
-- Name: payment_transactions id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.payment_transactions ALTER COLUMN id SET DEFAULT nextval('public.payment_transactions_id_seq'::regclass);


--
-- TOC entry 5181 (class 2604 OID 173614)
-- Name: percentage_discounts id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.percentage_discounts ALTER COLUMN id SET DEFAULT nextval('public.percentage_discounts_id_seq'::regclass);


--
-- TOC entry 5182 (class 2604 OID 173629)
-- Name: personal_access_tokens id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.personal_access_tokens ALTER COLUMN id SET DEFAULT nextval('public.personal_access_tokens_id_seq'::regclass);


--
-- TOC entry 5183 (class 2604 OID 173640)
-- Name: popular_searches id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.popular_searches ALTER COLUMN id SET DEFAULT nextval('public.popular_searches_id_seq'::regclass);


--
-- TOC entry 5186 (class 2604 OID 173657)
-- Name: product_collections id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.product_collections ALTER COLUMN id SET DEFAULT nextval('public.product_collections_id_seq'::regclass);


--
-- TOC entry 5189 (class 2604 OID 173666)
-- Name: product_color_variants id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.product_color_variants ALTER COLUMN id SET DEFAULT nextval('public.product_color_variants_id_seq'::regclass);


--
-- TOC entry 5191 (class 2604 OID 173682)
-- Name: product_images id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.product_images ALTER COLUMN id SET DEFAULT nextval('public.product_images_id_seq'::regclass);


--
-- TOC entry 5195 (class 2604 OID 173706)
-- Name: product_questions id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.product_questions ALTER COLUMN id SET DEFAULT nextval('public.product_questions_id_seq'::regclass);


--
-- TOC entry 5197 (class 2604 OID 173728)
-- Name: product_reviews id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.product_reviews ALTER COLUMN id SET DEFAULT nextval('public.product_reviews_id_seq'::regclass);


--
-- TOC entry 5202 (class 2604 OID 173753)
-- Name: product_size_variants id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.product_size_variants ALTER COLUMN id SET DEFAULT nextval('public.product_size_variants_id_seq'::regclass);


--
-- TOC entry 5205 (class 2604 OID 173770)
-- Name: productos_auditoria id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.productos_auditoria ALTER COLUMN id SET DEFAULT nextval('public.productos_auditoria_id_seq'::regclass);


--
-- TOC entry 5209 (class 2604 OID 173788)
-- Name: products id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.products ALTER COLUMN id SET DEFAULT nextval('public.products_id_seq'::regclass);


--
-- TOC entry 5215 (class 2604 OID 173814)
-- Name: question_answers id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.question_answers ALTER COLUMN id SET DEFAULT nextval('public.question_answers_id_seq'::regclass);


--
-- TOC entry 5218 (class 2604 OID 173837)
-- Name: review_votes id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.review_votes ALTER COLUMN id SET DEFAULT nextval('public.review_votes_id_seq'::regclass);


--
-- TOC entry 5220 (class 2604 OID 173853)
-- Name: search_history id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.search_history ALTER COLUMN id SET DEFAULT nextval('public.search_history_id_seq'::regclass);


--
-- TOC entry 5158 (class 2604 OID 173516)
-- Name: shipping_methods id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.shipping_methods ALTER COLUMN id SET DEFAULT nextval('public.shipping_methods_id_seq'::regclass);


--
-- TOC entry 5222 (class 2604 OID 173888)
-- Name: shipping_price_rules id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.shipping_price_rules ALTER COLUMN id SET DEFAULT nextval('public.shipping_price_rules_id_seq'::regclass);


--
-- TOC entry 5226 (class 2604 OID 173906)
-- Name: site_settings id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.site_settings ALTER COLUMN id SET DEFAULT nextval('public.site_settings_id_seq'::regclass);


--
-- TOC entry 5229 (class 2604 OID 173930)
-- Name: sizes id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.sizes ALTER COLUMN id SET DEFAULT nextval('public.sizes_id_seq'::regclass);


--
-- TOC entry 5232 (class 2604 OID 173947)
-- Name: sliders id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.sliders ALTER COLUMN id SET DEFAULT nextval('public.sliders_id_seq'::regclass);


--
-- TOC entry 5237 (class 2604 OID 173972)
-- Name: stock_history id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.stock_history ALTER COLUMN id SET DEFAULT nextval('public.stock_history_id_seq'::regclass);


--
-- TOC entry 5238 (class 2604 OID 173981)
-- Name: user_addresses id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.user_addresses ALTER COLUMN id SET DEFAULT nextval('public.user_addresses_id_seq'::regclass);


--
-- TOC entry 5246 (class 2604 OID 174009)
-- Name: user_applied_discounts id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.user_applied_discounts ALTER COLUMN id SET DEFAULT nextval('public.user_applied_discounts_id_seq'::regclass);


--
-- TOC entry 5249 (class 2604 OID 174026)
-- Name: variant_images id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.variant_images ALTER COLUMN id SET DEFAULT nextval('public.variant_images_id_seq'::regclass);


--
-- TOC entry 5253 (class 2604 OID 174050)
-- Name: wishlist id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.wishlist ALTER COLUMN id SET DEFAULT nextval('public.wishlist_id_seq'::regclass);


--
-- TOC entry 5541 (class 0 OID 172914)
-- Dependencies: 217
-- Data for Name: access_tokens; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.access_tokens (id, user_id, token, ip_address, user_agent, created_at, expires_at, is_revoked, trial548) FROM stdin;
\.


--
-- TOC entry 5544 (class 0 OID 172946)
-- Dependencies: 220
-- Data for Name: admin_notification_dismissals; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.admin_notification_dismissals (id, admin_id, notification_key, dismissed_at, trial548) FROM stdin;
1	6860007924a6a	payment:5	2025-11-21 13:42:41	T
\.


--
-- TOC entry 5546 (class 0 OID 172964)
-- Dependencies: 222
-- Data for Name: announcements; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.announcements (id, type, title, message, subtitle, button_text, button_link, image, background_color, text_color, icon, priority, is_active, start_date, end_date, created_at, updated_at, trial548) FROM stdin;
2	promo_banner	¡Oferta 3x2!	¡Compra 2 prendas y llévate la 3ra con 50% de descuento!	Válido hasta el 30 de junio o hasta agotar existencias	Aprovechar oferta	/tienda/tienda.php?promo=3x2	\N	#ff6b6b	#ffffff	fa-tags	5	1	\N	\N	2025-11-11 15:38:07	2025-11-11 15:42:12	T
\.


--
-- TOC entry 5547 (class 0 OID 172990)
-- Dependencies: 223
-- Data for Name: audit_categories; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.audit_categories (audit_id, category_id, action_type, old_name, new_name, action_date, trial548) FROM stdin;
1	9	INSERT	\N	Electrónica	2025-09-23 09:06:46	T
2	9	DELETE	Electrónica	\N	2025-11-01 17:30:20	T
3	5	UPDATE	Accesorios	Accesorios	2025-11-01 17:31:00	T
4	1	UPDATE	Vestidos	Vestidos	2025-11-01 17:31:44	T
5	1	UPDATE	Vestidos	Vestidos	2025-11-01 17:35:33	T
6	5	UPDATE	Accesorios	Accesorios	2025-11-01 17:35:33	T
7	5	UPDATE	Accesorios	Accesorios	2025-11-01 17:36:00	T
8	1	UPDATE	Vestidos	Vestidos	2025-11-01 17:36:13	T
9	2	UPDATE	Conjuntos	Conjuntos	2025-11-01 17:38:02	T
10	3	UPDATE	Pijamas	Pijamas	2025-11-01 18:15:46	T
11	4	UPDATE	Ropa Deportiva	Ropa Deportiva	2025-11-01 18:17:09	T
\.


--
-- TOC entry 5549 (class 0 OID 173001)
-- Dependencies: 225
-- Data for Name: audit_orders; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.audit_orders (id, orden_id, accion, usuario_id, sql_usuario, fecha, detalles, trial548) FROM stdin;
1	5	INSERT	6861e06ddcf49	root@localhost	2025-09-23 09:05:22	Se creó la orden #TEST001 con total $0.00	T
2	6	INSERT	6861e06ddcf49	root@localhost	2025-10-05 00:18:55	Se creó la orden #ORD20251005F8BADD con total $176000.00	T
3	7	INSERT	6861e06ddcf49	root@localhost	2025-10-08 13:23:51	Se creó la orden #ORD2025100876BCAF con total $148000.00	T
4	8	INSERT	6861e06ddcf49	root@localhost	2025-10-08 13:40:47	Se creó la orden #ORD20251008F47279 con total $183000.00	T
5	9	INSERT	6861e06ddcf49	root@localhost	2025-10-08 15:53:21	Se creó la orden #ORD2025100817A4CE con total $148000.00	T
6	10	INSERT	6861e06ddcf49	root@localhost	2025-10-08 16:17:18	Se creó la orden #ORD20251008E71B8B con total $218000.00	T
7	11	INSERT	6861e06ddcf49	root@localhost	2025-10-09 08:49:15	Se creó la orden #ORD20251009BC0908 con total $183000.00	T
8	12	INSERT	6861e06ddcf49	root@localhost	2025-10-09 08:59:05	Se creó la orden #ORD2025100997DEB3 con total $36000.00	T
9	13	INSERT	6861e06ddcf49	root@localhost	2025-10-09 09:10:14	Se creó la orden #ORD202510096EC1B1 con total $43000.00	T
10	14	INSERT	6861e06ddcf49	root@localhost	2025-10-09 09:35:21	Se creó la orden #ORD20251009985677 con total $43000.00	T
11	15	INSERT	6861e06ddcf49	root@localhost	2025-10-09 11:57:04	Se creó la orden #ORD202510090BFA71 con total $43000.00	T
12	16	INSERT	6861e06ddcf49	root@localhost	2025-10-09 12:04:00	Se creó la orden #ORD20251009037EF6 con total $43000.00	T
13	17	INSERT	6861e06ddcf49	root@localhost	2025-10-09 12:36:32	Se creó la orden #ORD20251009058E42 con total $78000.00	T
14	18	INSERT	6861e06ddcf49	root@localhost	2025-10-09 12:47:28	Se creó la orden #ORD202510090588A9 con total $43000.00	T
15	19	INSERT	6861e06ddcf49	root@localhost	2025-10-09 12:53:32	Se creó la orden #ORD20251009C69ABB con total $43000.00	T
16	20	INSERT	6861e06ddcf49	root@localhost	2025-10-09 12:58:35	Se creó la orden #ORD20251009B7E086 con total $358000.00	T
17	21	INSERT	6861e06ddcf49	root@localhost	2025-10-09 13:07:49	Se creó la orden #ORD202510095B1EA9 con total $43000.00	T
18	22	INSERT	6861e06ddcf49	root@localhost	2025-10-09 13:18:44	Se creó la orden #ORD202510094B58E8 con total $43000.00	T
19	23	INSERT	6861e06ddcf49	root@localhost	2025-10-09 13:29:47	Se creó la orden #ORD20251009B85DC4 con total $78000.00	T
20	21	UPDATE	6861e06ddcf49	root@localhost	2025-10-11 19:31:12	Orden actualizada. Estado: pending → processing. Total: $43000.00 → $43000.00	T
21	21	UPDATE	6861e06ddcf49	root@localhost	2025-10-11 19:35:32	Orden actualizada. Estado: processing → shipped. Total: $43000.00 → $43000.00	T
22	13	DELETE	6861e06ddcf49	root@localhost	2025-10-11 21:07:16	Se eliminó la orden #ORD202510096EC1B1 con total $43000.00	T
23	9	DELETE	6861e06ddcf49	root@localhost	2025-10-11 21:09:14	Se eliminó la orden #ORD2025100817A4CE con total $148000.00	T
24	8	DELETE	6861e06ddcf49	root@localhost	2025-10-11 21:09:20	Se eliminó la orden #ORD20251008F47279 con total $183000.00	T
25	6	DELETE	6861e06ddcf49	root@localhost	2025-10-11 22:48:43	Se eliminó la orden #ORD20251005F8BADD con total $176000.00	T
26	5	DELETE	6861e06ddcf49	root@localhost	2025-10-11 22:48:47	Se eliminó la orden #TEST001 con total $0.00	T
27	7	DELETE	6861e06ddcf49	root@localhost	2025-10-11 22:48:50	Se eliminó la orden #ORD2025100876BCAF con total $148000.00	T
28	10	DELETE	6861e06ddcf49	root@localhost	2025-10-11 22:48:54	Se eliminó la orden #ORD20251008E71B8B con total $218000.00	T
29	11	DELETE	6861e06ddcf49	root@localhost	2025-10-11 22:48:59	Se eliminó la orden #ORD20251009BC0908 con total $183000.00	T
30	12	DELETE	6861e06ddcf49	root@localhost	2025-10-11 22:56:49	Se eliminó la orden #ORD2025100997DEB3 con total $36000.00	T
31	14	DELETE	6861e06ddcf49	root@localhost	2025-10-11 22:56:49	Se eliminó la orden #ORD20251009985677 con total $43000.00	T
39	15	UPDATE	6861e06ddcf49	root@localhost	2025-10-12 15:07:14	Orden actualizada. Estado: pending → processing. Total: $43000.00 → $43000.00	T
40	16	UPDATE	6861e06ddcf49	root@localhost	2025-10-12 15:07:14	Orden actualizada. Estado: pending → processing. Total: $43000.00 → $43000.00	T
41	17	UPDATE	6861e06ddcf49	root@localhost	2025-10-12 15:07:14	Orden actualizada. Estado: pending → processing. Total: $78000.00 → $78000.00	T
42	15	UPDATE	6861e06ddcf49	root@localhost	2025-10-12 15:08:30	Orden actualizada. Estado: processing → pending. Total: $43000.00 → $43000.00	T
43	15	UPDATE	6861e06ddcf49	root@localhost	2025-10-12 15:08:30	Orden actualizada. Estado: pending → processing. Total: $43000.00 → $43000.00	T
44	17	UPDATE	6861e06ddcf49	root@localhost	2025-10-12 15:08:57	Orden actualizada. Estado: processing → pending. Total: $78000.00 → $78000.00	T
45	16	UPDATE	6861e06ddcf49	root@localhost	2025-10-12 15:08:57	Orden actualizada. Estado: processing → pending. Total: $43000.00 → $43000.00	T
46	15	UPDATE	6861e06ddcf49	root@localhost	2025-10-12 15:08:57	Orden actualizada. Estado: processing → pending. Total: $43000.00 → $43000.00	T
47	17	UPDATE	6861e06ddcf49	root@localhost	2025-10-12 15:23:52	Orden actualizada. Estado: pending → processing. Total: $78000.00 → $78000.00	T
48	16	UPDATE	6861e06ddcf49	root@localhost	2025-10-12 15:23:52	Orden actualizada. Estado: pending → processing. Total: $43000.00 → $43000.00	T
49	15	UPDATE	6861e06ddcf49	root@localhost	2025-10-12 15:23:52	Orden actualizada. Estado: pending → processing. Total: $43000.00 → $43000.00	T
50	15	DELETE	6861e06ddcf49	root@localhost	2025-10-12 15:24:01	Se eliminó la orden #ORD202510090BFA71 con total $43000.00	T
51	16	DELETE	6861e06ddcf49	root@localhost	2025-10-12 15:24:01	Se eliminó la orden #ORD20251009037EF6 con total $43000.00	T
52	17	DELETE	6861e06ddcf49	root@localhost	2025-10-12 15:24:01	Se eliminó la orden #ORD20251009058E42 con total $78000.00	T
53	24	INSERT	6861e06ddcf49	root@localhost	2025-10-12 17:00:32	Se creó la orden #TEST-20251012170032 con total $100.00	T
54	24	UPDATE	6861e06ddcf49	root@localhost	2025-10-12 17:00:32	Orden actualizada. Estado: processing → shipped. Total: $100.00 → $100.00	T
55	25	INSERT	6861e06ddcf49	root@localhost	2025-10-12 17:04:35	Se creó la orden #TEST-20251012170435 con total $100.00	T
56	25	UPDATE	6861e06ddcf49	root@localhost	2025-10-12 17:04:35	Orden actualizada. Estado: processing → shipped. Total: $100.00 → $100.00	T
57	25	UPDATE	6861e06ddcf49	root@localhost	2025-10-12 17:04:35	Orden actualizada. Estado: shipped → delivered. Total: $100.00 → $100.00	T
58	23	UPDATE	6861e06ddcf49	root@localhost	2025-10-12 17:10:18	Orden actualizada. Estado: pending → shipped. Total: $78000.00 → $78000.00	T
59	26	INSERT	6861e06ddcf49	root@localhost	2025-10-12 17:19:34	Se creó la orden #ORD-20251012171934 con total $100.00	T
60	18	DELETE	6861e06ddcf49	root@localhost	2025-10-12 20:56:39	Se eliminó la orden #ORD202510090588A9 con total $43000.00	T
61	19	DELETE	6861e06ddcf49	root@localhost	2025-10-12 20:56:39	Se eliminó la orden #ORD20251009C69ABB con total $43000.00	T
62	20	DELETE	6861e06ddcf49	root@localhost	2025-10-12 20:56:39	Se eliminó la orden #ORD20251009B7E086 con total $358000.00	T
63	21	DELETE	6861e06ddcf49	root@localhost	2025-10-12 20:56:39	Se eliminó la orden #ORD202510095B1EA9 con total $43000.00	T
64	22	DELETE	6861e06ddcf49	root@localhost	2025-10-12 20:56:39	Se eliminó la orden #ORD202510094B58E8 con total $43000.00	T
65	23	DELETE	6861e06ddcf49	root@localhost	2025-10-12 20:56:39	Se eliminó la orden #ORD20251009B85DC4 con total $78000.00	T
66	24	DELETE	6861e06ddcf49	root@localhost	2025-10-12 20:56:39	Se eliminó la orden #TEST-20251012170032 con total $100.00	T
67	25	DELETE	6861e06ddcf49	root@localhost	2025-10-12 20:56:39	Se eliminó la orden #TEST-20251012170435 con total $100.00	T
68	26	DELETE	6861e06ddcf49	root@localhost	2025-10-12 20:56:39	Se eliminó la orden #ORD-20251012171934 con total $100.00	T
69	27	INSERT	6861e06ddcf49	root@localhost	2025-10-13 10:51:32	Se creó la orden #ORD202510134E5C9A con total $113000.00	T
70	27	UPDATE	6861e06ddcf49	root@localhost	2025-10-13 10:53:54	Orden actualizada. Estado: pending → shipped. Total: $113000.00 → $113000.00	T
71	27	UPDATE	6861e06ddcf49	root@localhost	2025-10-13 11:28:43	Orden actualizada. Estado: shipped → pending. Total: $113000.00 → $113000.00	T
72	27	DELETE	6861e06ddcf49	root@localhost	2025-10-13 11:28:52	Se eliminó la orden #ORD202510134E5C9A con total $113000.00	T
73	28	INSERT	6861e06ddcf49	root@localhost	2025-10-13 11:31:51	Se creó la orden #ORD202510137D261E con total $148000.00	T
74	28	DELETE	6861e06ddcf49	root@localhost	2025-10-13 11:41:08	Se eliminó la orden #ORD202510137D261E con total $148000.00	T
75	29	INSERT	6861e06ddcf49	root@localhost	2025-10-13 11:44:46	Se creó la orden #ORD20251013E7E31E con total $43000.00	T
76	29	UPDATE	6861e06ddcf49	root@localhost	2025-10-13 11:46:45	Orden actualizada. Estado: pending → shipped. Total: $43000.00 → $43000.00	T
77	29	DELETE	6861e06ddcf49	root@localhost	2025-10-13 11:54:18	Se eliminó la orden #ORD20251013E7E31E con total $43000.00	T
78	30	INSERT	6861e06ddcf49	root@localhost	2025-10-13 11:58:03	Se creó la orden #ORD20251013B49894 con total $43000.00	T
79	30	UPDATE	6861e06ddcf49	root@localhost	2025-10-13 12:08:17	Orden actualizada. Estado: pending → shipped. Total: $43000.00 → $43000.00	T
80	30	DELETE	6861e06ddcf49	root@localhost	2025-11-12 09:17:13	Se eliminó la orden #ORD20251013B49894 con total $43000.00	T
81	1	INSERT	6861e06ddcf49	root@localhost	2025-11-15 22:10:11	Se creó la orden #TEST37D9B8 con total $45000.00	T
82	2	INSERT	6861e06ddcf49	root@localhost	2025-11-15 22:17:00	Se creó la orden #ORD20251115C9DBC9 con total $36000.00	T
83	3	INSERT	6861e06ddcf49	root@localhost	2025-11-16 09:09:59	Se creó la orden #ORD2025111677ADAB con total $35000.00	T
84	4	INSERT	6861e06ddcf49	root@localhost	2025-11-16 09:12:17	Se creó la orden #ORD20251116134F34 con total $35000.00	T
85	5	INSERT	6861e06ddcf49	root@localhost	2025-11-16 09:16:36	Se creó la orden #ORD20251116448A7E con total $35000.00	T
86	6	INSERT	6861e06ddcf49	root@localhost	2025-11-16 09:46:56	Se creó la orden #ORD202511160CB9A1 con total $35000.00	T
87	7	INSERT	6861e06ddcf49	root@localhost	2025-11-16 09:54:06	Se creó la orden #ORD20251116EAA8AB con total $35000.00	T
88	7	UPDATE	6861e06ddcf49	root@localhost	2025-11-16 10:55:10	Orden actualizada. Estado: pending → processing. Total: $35000.00 → $35000.00	T
89	7	UPDATE	6861e06ddcf49	root@localhost	2025-11-16 11:49:52	Orden actualizada. Estado: processing → shipped. Total: $35000.00 → $35000.00	T
90	8	INSERT	6861e06ddcf49	root@localhost	2025-11-16 11:58:11	Se creó la orden #ORD202511163AF156 con total $43000.00	T
91	7	UPDATE	6861e06ddcf49	root@localhost	2025-11-16 12:13:02	Orden actualizada. Estado: shipped → delivered. Total: $35000.00 → $35000.00	T
92	7	UPDATE	6861e06ddcf49	root@localhost	2025-11-16 12:13:24	Orden actualizada. Estado: delivered → shipped. Total: $35000.00 → $35000.00	T
93	7	UPDATE	6861e06ddcf49	root@localhost	2025-11-16 12:13:38	Orden actualizada. Estado: shipped → delivered. Total: $35000.00 → $35000.00	T
94	7	UPDATE	6861e06ddcf49	root@localhost	2025-11-16 12:16:59	Orden actualizada. Estado: delivered → shipped. Total: $35000.00 → $35000.00	T
95	7	UPDATE	6861e06ddcf49	root@localhost	2025-11-16 12:17:05	Orden actualizada. Estado: shipped → delivered. Total: $35000.00 → $35000.00	T
96	7	UPDATE	6861e06ddcf49	root@localhost	2025-11-16 12:21:58	Orden actualizada. Estado: delivered → shipped. Total: $35000.00 → $35000.00	T
97	7	UPDATE	6861e06ddcf49	root@localhost	2025-11-16 12:22:07	Orden actualizada. Estado: shipped → delivered. Total: $35000.00 → $35000.00	T
98	7	UPDATE	6861e06ddcf49	root@localhost	2025-11-16 12:28:18	Orden actualizada. Estado: delivered → shipped. Total: $35000.00 → $35000.00	T
99	7	UPDATE	6861e06ddcf49	root@localhost	2025-11-16 12:28:24	Orden actualizada. Estado: shipped → delivered. Total: $35000.00 → $35000.00	T
100	7	UPDATE	6861e06ddcf49	root@localhost	2025-11-16 12:33:16	Orden actualizada. Estado: delivered → shipped. Total: $35000.00 → $35000.00	T
101	7	UPDATE	6861e06ddcf49	root@localhost	2025-11-16 12:33:22	Orden actualizada. Estado: shipped → delivered. Total: $35000.00 → $35000.00	T
102	7	UPDATE	6861e06ddcf49	root@localhost	2025-11-16 12:36:17	Orden actualizada. Estado: delivered → shipped. Total: $35000.00 → $35000.00	T
103	7	UPDATE	6861e06ddcf49	root@localhost	2025-11-16 12:36:22	Orden actualizada. Estado: shipped → delivered. Total: $35000.00 → $35000.00	T
104	7	UPDATE	6861e06ddcf49	root@localhost	2025-11-16 12:42:53	Orden actualizada. Estado: delivered → shipped. Total: $35000.00 → $35000.00	T
105	7	UPDATE	6861e06ddcf49	root@localhost	2025-11-16 12:43:00	Orden actualizada. Estado: shipped → delivered. Total: $35000.00 → $35000.00	T
106	7	UPDATE	6861e06ddcf49	root@localhost	2025-11-16 12:45:57	Orden actualizada. Estado: delivered → shipped. Total: $35000.00 → $35000.00	T
107	7	UPDATE	6861e06ddcf49	root@localhost	2025-11-16 12:46:22	Orden actualizada. Estado: shipped → delivered. Total: $35000.00 → $35000.00	T
108	7	UPDATE	6861e06ddcf49	root@localhost	2025-11-16 12:48:44	Orden actualizada. Estado: delivered → shipped. Total: $35000.00 → $35000.00	T
109	7	UPDATE	6861e06ddcf49	root@localhost	2025-11-16 12:48:49	Orden actualizada. Estado: shipped → delivered. Total: $35000.00 → $35000.00	T
110	7	UPDATE	6861e06ddcf49	root@localhost	2025-11-16 12:49:19	Orden actualizada. Estado: delivered → shipped. Total: $35000.00 → $35000.00	T
111	7	UPDATE	6861e06ddcf49	root@localhost	2025-11-16 12:49:24	Orden actualizada. Estado: shipped → delivered. Total: $35000.00 → $35000.00	T
112	7	UPDATE	6861e06ddcf49	root@localhost	2025-11-16 12:50:15	Orden actualizada. Estado: delivered → shipped. Total: $35000.00 → $35000.00	T
113	7	UPDATE	6861e06ddcf49	root@localhost	2025-11-16 12:50:21	Orden actualizada. Estado: shipped → delivered. Total: $35000.00 → $35000.00	T
114	7	UPDATE	6861e06ddcf49	root@localhost	2025-11-16 12:51:37	Orden actualizada. Estado: delivered → shipped. Total: $35000.00 → $35000.00	T
115	7	UPDATE	6861e06ddcf49	root@localhost	2025-11-16 12:51:42	Orden actualizada. Estado: shipped → delivered. Total: $35000.00 → $35000.00	T
116	7	UPDATE	6861e06ddcf49	root@localhost	2025-11-16 12:55:36	Orden actualizada. Estado: delivered → shipped. Total: $35000.00 → $35000.00	T
117	7	UPDATE	6861e06ddcf49	root@localhost	2025-11-16 12:55:41	Orden actualizada. Estado: shipped → delivered. Total: $35000.00 → $35000.00	T
118	7	UPDATE	6861e06ddcf49	root@localhost	2025-11-16 13:02:11	Orden actualizada. Estado: delivered → shipped. Total: $35000.00 → $35000.00	T
119	8	UPDATE	6861e06ddcf49	root@localhost	2025-11-16 18:53:06	Orden actualizada. Estado: pending → cancelled. Total: $43000.00 → $43000.00	T
120	8	UPDATE	6861e06ddcf49	root@localhost	2025-11-16 20:01:57	Orden actualizada. Estado: cancelled → shipped. Total: $43000.00 → $43000.00	T
121	8	UPDATE	6861e06ddcf49	root@localhost	2025-11-16 20:02:07	Orden actualizada. Estado: shipped → cancelled. Total: $43000.00 → $43000.00	T
122	8	UPDATE	6861e06ddcf49	root@localhost	2025-11-16 20:02:51	Orden actualizada. Estado: cancelled → shipped. Total: $43000.00 → $43000.00	T
123	8	UPDATE	6861e06ddcf49	root@localhost	2025-11-16 20:03:03	Orden actualizada. Estado: shipped → cancelled. Total: $43000.00 → $43000.00	T
124	8	UPDATE	6861e06ddcf49	root@localhost	2025-11-16 20:03:54	Orden actualizada. Estado: cancelled → shipped. Total: $43000.00 → $43000.00	T
125	8	UPDATE	6861e06ddcf49	root@localhost	2025-11-16 20:12:56	Orden actualizada. Estado: shipped → cancelled. Total: $43000.00 → $43000.00	T
126	8	UPDATE	6861e06ddcf49	root@localhost	2025-11-16 22:00:43	Orden actualizada. Estado: cancelled → refunded. Total: $43000.00 → $43000.00	T
127	8	UPDATE	6861e06ddcf49	root@localhost	2025-11-16 22:04:07	Orden actualizada. Estado: refunded → processing. Total: $43000.00 → $43000.00	T
128	8	UPDATE	6861e06ddcf49	root@localhost	2025-11-16 22:04:30	Orden actualizada. Estado: processing → refunded. Total: $43000.00 → $43000.00	T
129	8	UPDATE	6861e06ddcf49	root@localhost	2025-11-16 22:07:02	Orden actualizada. Estado: refunded → cancelled. Total: $43000.00 → $43000.00	T
130	8	UPDATE	6861e06ddcf49	root@localhost	2025-11-16 22:07:14	Orden actualizada. Estado: cancelled → refunded. Total: $43000.00 → $43000.00	T
131	8	UPDATE	6861e06ddcf49	root@localhost	2025-11-16 22:09:33	Orden actualizada. Estado: refunded → cancelled. Total: $43000.00 → $43000.00	T
132	8	UPDATE	6861e06ddcf49	root@localhost	2025-11-16 22:09:43	Orden actualizada. Estado: cancelled → refunded. Total: $43000.00 → $43000.00	T
133	8	UPDATE	6861e06ddcf49	root@localhost	2025-11-16 22:12:09	Orden actualizada. Estado: refunded → delivered. Total: $43000.00 → $43000.00	T
134	8	UPDATE	6861e06ddcf49	root@localhost	2025-11-16 22:13:09	Orden actualizada. Estado: delivered → cancelled. Total: $43000.00 → $43000.00	T
135	8	UPDATE	6861e06ddcf49	root@localhost	2025-11-16 22:13:17	Orden actualizada. Estado: cancelled → refunded. Total: $43000.00 → $43000.00	T
136	8	UPDATE	6861e06ddcf49	root@localhost	2025-11-16 22:21:19	Orden actualizada. Estado: refunded → delivered. Total: $43000.00 → $43000.00	T
137	8	UPDATE	6861e06ddcf49	root@localhost	2025-11-16 22:21:36	Orden actualizada. Estado: delivered → refunded. Total: $43000.00 → $43000.00	T
138	8	UPDATE	6861e06ddcf49	root@localhost	2025-11-16 22:21:55	Orden actualizada. Estado: refunded → cancelled. Total: $43000.00 → $43000.00	T
139	8	UPDATE	6861e06ddcf49	root@localhost	2025-11-16 22:22:09	Orden actualizada. Estado: cancelled → refunded. Total: $43000.00 → $43000.00	T
140	8	UPDATE	6861e06ddcf49	root@localhost	2025-11-16 22:23:57	Orden actualizada. Estado: refunded → cancelled. Total: $43000.00 → $43000.00	T
141	8	UPDATE	6861e06ddcf49	root@localhost	2025-11-16 22:24:11	Orden actualizada. Estado: cancelled → refunded. Total: $43000.00 → $43000.00	T
142	8	UPDATE	6861e06ddcf49	root@localhost	2025-11-16 22:33:36	Orden actualizada. Estado: refunded → cancelled. Total: $43000.00 → $43000.00	T
143	8	UPDATE	6861e06ddcf49	root@localhost	2025-11-16 22:37:00	Orden actualizada. Estado: cancelled → refunded. Total: $43000.00 → $43000.00	T
144	8	UPDATE	6861e06ddcf49	root@localhost	2025-11-16 22:37:40	Orden actualizada. Estado: refunded → cancelled. Total: $43000.00 → $43000.00	T
145	8	UPDATE	6861e06ddcf49	root@localhost	2025-11-16 22:38:38	Orden actualizada. Estado: cancelled → delivered. Total: $43000.00 → $43000.00	T
146	6	UPDATE	6861e06ddcf49	root@localhost	2025-11-16 23:11:45	Orden actualizada. Estado: pending → cancelled. Total: $35000.00 → $35000.00	T
147	6	UPDATE	6861e06ddcf49	root@localhost	2025-11-16 23:12:38	Orden actualizada. Estado: cancelled → refunded. Total: $35000.00 → $35000.00	T
148	5	UPDATE	6861e06ddcf49	root@localhost	2025-11-17 13:04:02	Orden actualizada. Estado: pending → delivered. Total: $35000.00 → $35000.00	T
149	6	DELETE	6861e06ddcf49	root@localhost	2025-11-22 22:58:09	Se eliminó la orden #ORD202511160CB9A1 con total $35000.00	T
150	7	UPDATE	6861e06ddcf49	root@localhost	2025-11-22 22:58:30	Orden actualizada. Estado: shipped → delivered. Total: $35000.00 → $35000.00	T
151	4	UPDATE	6861e06ddcf49	root@localhost	2025-11-22 22:58:52	Orden actualizada. Estado: pending → delivered. Total: $35000.00 → $35000.00	T
152	3	UPDATE	6861e06ddcf49	root@localhost	2025-11-22 22:59:45	Orden actualizada. Estado: pending → delivered. Total: $35000.00 → $35000.00	T
153	2	UPDATE	6861e06ddcf49	root@localhost	2025-11-22 22:59:45	Orden actualizada. Estado: pending → delivered. Total: $36000.00 → $36000.00	T
154	1	UPDATE	6861e06ddcf49	root@localhost	2025-11-22 22:59:45	Orden actualizada. Estado: pending → delivered. Total: $45000.00 → $45000.00	T
155	9	INSERT	6861e06ddcf49	root@localhost	2025-11-22 23:07:44	Se creó la orden #ORD2025112208851F con total $583000.00	T
156	9	UPDATE	6861e06ddcf49	root@localhost	2025-11-22 23:21:01	Orden actualizada. Estado: pending → shipped. Total: $583000.00 → $583000.00	T
157	10	INSERT	6922930b94130	root@localhost	2025-11-22 23:54:53	Se creó la orden #ORD20251122DBE295 con total $358000.00	T
158	11	INSERT	6861e06ddcf49	root@localhost	2025-11-23 00:04:19	Se creó la orden #ORD2025112335EAC5 con total $308000.00	T
159	11	UPDATE	6861e06ddcf49	root@localhost	2025-11-23 00:06:23	Orden actualizada. Estado: pending → shipped. Total: $308000.00 → $308000.00	T
160	12	INSERT	6861e06ddcf49	root@localhost	2025-11-25 22:09:34	Se creó la orden #ORD20251125EDC719 con total $88000.00	T
161	13	INSERT	6861e06ddcf49	root@localhost	2025-11-25 22:43:44	Se creó la orden #ORD2025112501BD10 con total $53000.00	T
162	14	INSERT	6861e06ddcf49	root@localhost	2025-11-25 23:05:14	Se creó la orden #ORD20251125A8B68D con total $53000.00	T
163	15	INSERT	6861e06ddcf49	root@localhost	2025-11-25 23:09:51	Se creó la orden #ORD20251125FB1BE6 con total $98000.00	T
164	16	INSERT	6861e06ddcf49	root@localhost	2025-11-25 23:11:22	Se creó la orden #ORD20251125AA48F4 con total $88000.00	T
165	17	INSERT	6861e06ddcf49	root@localhost	2025-11-25 23:20:37	Se creó la orden #ORD202511255DCC59 con total $88000.00	T
166	17	UPDATE	6861e06ddcf49	root@localhost	2025-11-25 23:22:42	Orden actualizada. Estado: pending → processing. Total: $88000.00 → $88000.00	T
167	17	UPDATE	6861e06ddcf49	root@localhost	2025-11-25 23:23:20	Orden actualizada. Estado: processing → shipped. Total: $88000.00 → $88000.00	T
168	17	UPDATE	6861e06ddcf49	root@localhost	2025-11-25 23:23:44	Orden actualizada. Estado: shipped → delivered. Total: $88000.00 → $88000.00	T
169	16	UPDATE	6861e06ddcf49	root@localhost	2025-11-25 23:27:00	Orden actualizada. Estado: pending → delivered. Total: $88000.00 → $88000.00	T
170	15	UPDATE	6861e06ddcf49	root@localhost	2025-11-26 00:11:24	Orden actualizada. Estado: pending → cancelled. Total: $98000.00 → $98000.00	T
171	13	UPDATE	6861e06ddcf49	root@localhost	2025-11-26 00:17:11	Orden actualizada. Estado: pending → cancelled. Total: $53000.00 → $53000.00	T
172	12	UPDATE	6861e06ddcf49	root@localhost	2025-11-26 00:23:38	Orden actualizada. Estado: pending → cancelled. Total: $88000.00 → $88000.00	T
173	15	UPDATE	6861e06ddcf49	root@localhost	2025-11-26 00:24:04	Orden actualizada. Estado: cancelled → refunded. Total: $98000.00 → $98000.00	T
174	18	INSERT	6927787315395	root@localhost	2025-11-26 17:14:59	Se creó la orden #ORD202511263173E3 con total $548000.00	T
175	19	INSERT	6861e06ddcf49	root@localhost	2025-11-26 17:19:31	Se creó la orden #ORD202511263E6FF6 con total $88000.00	T
176	19	UPDATE	6861e06ddcf49	root@localhost	2025-11-26 17:20:51	Orden actualizada. Estado: pending → processing. Total: $88000.00 → $88000.00	T
177	19	UPDATE	6861e06ddcf49	root@localhost	2025-11-26 17:21:14	Orden actualizada. Estado: processing → delivered. Total: $88000.00 → $88000.00	T
\.


--
-- TOC entry 5551 (class 0 OID 173023)
-- Dependencies: 227
-- Data for Name: audit_users; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.audit_users (id, usuario_id, accion, usuario_modificador, sql_usuario, fecha, detalles, trial548) FROM stdin;
1	TEST_USER_001	INSERT	TEST_USER_001	root@localhost	2025-09-23 09:05:33	Se creó el usuario: Usuario Prueba (prueba@ejemplo.com). Rol: customer	T
2	TEST_USER_001	UPDATE	TEST_USER_001	root@localhost	2025-09-23 09:05:33	Usuario actualizado. Cambios: Nombre: Usuario Prueba → Usuario Modificado. Rol: customer → admin. 	T
3	TEST_USER_001	DELETE	TEST_USER_001	root@localhost	2025-09-23 09:05:33	Se eliminó el usuario: Usuario Modificado (prueba@ejemplo.com). Rol: admin	T
4	68d315bcd96ef	INSERT	68d315bcd96ef	root@localhost	2025-09-23 16:48:45	Se creó el usuario: andres (andres90@gmail.com). Rol: customer	T
5	68d315cf194ba	INSERT	68d315cf194ba	root@localhost	2025-09-23 16:49:03	Se creó el usuario: Braian Andres Oquendo Durango (tracongames2@gmail.com). Rol: customer	T
6	6862b7448112f	UPDATE	6862b7448112f	root@localhost	2025-11-07 06:31:34	Usuario actualizado. Cambios: Rol: delivery → customer. Bloqueo: 0 → 1. 	T
7	691b491806be8	INSERT	691b491806be8	root@localhost	2025-11-17 11:11:04	Se creó el usuario: andres (andres80@gmail.com). Rol: customer	T
8	6922930b94130	INSERT	6922930b94130	root@localhost	2025-11-22 23:52:27	Se creó el usuario: Andres (andres90@gmai.com). Rol: customer	T
9	dbf462a655c64	INSERT	dbf462a655c64	root@localhost	2025-11-23 12:58:24	Se creó el usuario: juan (juan@gmail.com). Rol: customer	T
10	6926914b27f44	INSERT	6926914b27f44	root@localhost	2025-11-26 00:34:03	Se creó el usuario: Rodrigo (tracongamescorreos@gmail.com). Rol: customer	T
11	6927787315395	INSERT	6927787315395	root@localhost	2025-11-26 17:00:19	Se creó el usuario: prueba (prueba90@gmail.com). Rol: customer	T
12	c76ec6ef3df24	INSERT	c76ec6ef3df24	root@localhost	2025-11-27 09:23:52	Se creó el usuario: Leidy (leidy315@gmail.com). Rol: customer	T
13	b540a67d4b494	INSERT	b540a67d4b494	root@localhost	2025-11-27 22:46:56	Se creó el usuario: Braian777 (braian777@gmail.com). Rol: customer	T
14	b540a67d4b494	UPDATE	b540a67d4b494	root@localhost	2025-11-27 22:48:21	Usuario actualizado. Cambios: Teléfono:  → 3013636902. 	T
15	901178e935724	INSERT	901178e935724	root@localhost	2025-11-28 00:09:07	Se creó el usuario: test3 (test3@gmail.com). Rol: customer	T
16	901178e935724	UPDATE	901178e935724	root@localhost	2025-11-28 00:13:33	Usuario actualizado. Cambios: Nombre: test3 → test4. 	T
17	901178e935724	UPDATE	901178e935724	root@localhost	2025-11-28 00:14:30	Usuario actualizado. Cambios: Teléfono:  → 301262621. 	T
18	39ea4d4c39d54	INSERT	39ea4d4c39d54	root@localhost	2025-11-28 01:13:16	Se creó el usuario: test6 (test6@gmail.com). Rol: customer	T
\.


--
-- TOC entry 5553 (class 0 OID 173045)
-- Dependencies: 229
-- Data for Name: bank_account_config; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.bank_account_config (id, bank_code, account_number, account_type, account_holder, identification_type, identification_number, email, phone, is_active, created_by, created_at, updated_at, trial548) FROM stdin;
1	040	13311	ahorros	ffff	cc	1222224242	braianoquendurango@gmail.com	3013636902	0	6860007924a6a	2025-10-04 21:26:59	2025-10-04 21:27:46	T
2	031	13311	ahorros	Braian Oquendo	cc	1023526011	braianoquendurango@gmail.com	3013636902	1	6860007924a6a	2025-10-04 21:28:27	2025-11-17 14:36:07	T
\.


--
-- TOC entry 5555 (class 0 OID 173064)
-- Dependencies: 231
-- Data for Name: bulk_discount_rules; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.bulk_discount_rules (id, min_quantity, max_quantity, discount_percentage, is_active, created_at, updated_at, trial548) FROM stdin;
1	30	50	10.00	1	2025-07-27 15:50:11	2025-11-25 21:01:15	T
2	51	\N	20.00	1	2025-07-27 15:50:38	2025-07-27 15:50:46	T
\.


--
-- TOC entry 5556 (class 0 OID 173081)
-- Dependencies: 232
-- Data for Name: cache; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.cache (key, value, expiration, trial548) FROM stdin;
\.


--
-- TOC entry 5557 (class 0 OID 173088)
-- Dependencies: 233
-- Data for Name: cache_locks; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.cache_locks (key, owner, expiration, trial548) FROM stdin;
\.


--
-- TOC entry 5559 (class 0 OID 173096)
-- Dependencies: 235
-- Data for Name: cart_items; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.cart_items (id, cart_id, product_id, color_variant_id, size_variant_id, quantity, created_at, updated_at, trial548) FROM stdin;
1	7	71	16	26	1	2025-11-15 21:38:07	2025-11-15 21:38:07	T
12	8	71	27	36	10	2025-11-22 23:05:57	2025-11-22 23:05:57	T
13	9	71	28	37	5	2025-11-22 23:06:02	2025-11-22 23:06:02	T
22	16	71	35	47	1	2025-11-25 23:10:33	2025-11-25 23:10:33	T
23	17	71	36	48	1	2025-11-25 23:10:36	2025-11-25 23:10:36	T
29	21	74	31	43	1	2025-11-26 16:59:30	2025-11-26 16:59:30	T
30	22	73	30	41	1	2025-11-26 17:02:30	2025-11-26 17:02:30	T
37	27	71	35	47	10	2025-11-27 22:48:47	2025-11-27 22:48:47	T
41	30	73	30	41	1	2026-01-24 12:01:35	2026-01-24 12:01:52	T
42	31	73	30	41	4	2026-03-18 12:55:15	2026-03-18 12:55:15	T
\.


--
-- TOC entry 5561 (class 0 OID 173114)
-- Dependencies: 237
-- Data for Name: carts; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.carts (id, user_id, session_id, created_at, updated_at, trial551) FROM stdin;
30	\N	640o47fda0b3ucue1n1tq44ce4	2026-01-24 12:01:35	2026-01-24 12:01:35	T
31	\N	l2m0lj85rllko0hc9tr3tf9kln	2026-03-18 12:55:15	2026-03-18 12:55:15	T
\.


--
-- TOC entry 5563 (class 0 OID 173131)
-- Dependencies: 239
-- Data for Name: categories; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.categories (id, name, slug, description, image, parent_id, is_active, created_at, updated_at, trial551) FROM stdin;
1	Vestidos	vestidos	Vestidos infantiles para ocasiones especiales	uploads/categories/category_1762036573.jpeg	\N	1	2025-06-21 21:11:42	2025-11-01 17:36:13	T
2	Conjuntos	conjuntos	Conjuntos de ropa coordinados	uploads/categories/category_1762036682.jpg	\N	1	2025-06-21 21:11:42	2025-11-01 17:38:02	T
3	Pijamas	pijamas	Pijamas y ropa para dormir	uploads/categories/category_1762038946.webp	\N	1	2025-06-21 21:11:42	2025-11-01 18:15:46	T
4	Ropa Deportiva	ropa-deportiva	Ropa para actividades físicas	uploads/categories/category_1762039029.webp	\N	1	2025-06-21 21:11:42	2025-11-01 18:17:09	T
5	Accesorios	accesorios	Complementos y accesorios infantiles	uploads/categories/category_1762036560.jpg	\N	1	2025-06-21 21:11:42	2025-11-01 17:36:00	T
6	Ropa Casual	ropa-casual	Ropa informal para el día a día	\N	\N	1	2025-06-21 21:11:42	2025-06-21 21:11:42	T
7	Ropa Formal	ropa-formal	Ropa para eventos especiales	\N	\N	1	2025-06-21 21:11:42	2025-06-21 21:11:42	T
8	Ropa de Baño	ropa-de-bano	Trajes de baño y ropa playera	\N	\N	1	2025-06-21 21:11:42	2025-06-21 21:11:42	T
\.


--
-- TOC entry 5565 (class 0 OID 173155)
-- Dependencies: 241
-- Data for Name: collections; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.collections (id, name, slug, description, image, launch_date, is_active, created_at, updated_at, trial551) FROM stdin;
1	Verano Mágico	verano-magico	Colección de verano con colores vibrantes y diseños frescos	uploads/collections/collection_17620386951843.webp	2025-05-01	1	2025-06-29 00:15:58	2025-11-01 18:11:35	T
2	Aventura Infantil	aventura-infantil	Ropa cómoda y resistente para pequeños exploradores	uploads/collections/collection_17620387744925.jpg	2025-04-15	1	2025-06-29 00:15:58	2025-11-01 18:12:54	T
3	Dulces Sueños	dulces-suenos	Pijamas y ropa de dormir ultra suaves	uploads/collections/collection_17641016203035.jpg	2025-03-20	1	2025-06-29 00:15:58	2025-11-25 15:13:40	T
4	Colección Clásica	coleccion-clasica	Diseños atemporales para ocasiones especiales	uploads/collections/collection_17641015155481.jpg	2025-01-10	1	2025-06-29 00:15:58	2025-11-25 15:11:55	T
5	Mini Trendsetters	mini-trendsetters	Las últimas tendencias en moda infantil	uploads/collections/collection_17620386345155.jpg	2025-06-01	1	2025-06-29 00:15:58	2025-11-01 18:10:34	T
\.


--
-- TOC entry 5567 (class 0 OID 173179)
-- Dependencies: 243
-- Data for Name: colombian_banks; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.colombian_banks (id, bank_code, bank_name, is_active, trial551) FROM stdin;
1	001	Banco de Bogotá	1	T
2	002	Banco Popular	1	T
3	006	Banco Santander	1	T
4	007	BBVA Colombia	1	T
5	009	Citibank	1	T
6	012	Banco GNB Sudameris	1	T
7	013	Banco AV Villas	1	T
8	014	Banco de Occidente	1	T
9	019	Bancoomeva	1	T
10	023	Banco Itaú	1	T
11	031	Bancolombia	1	T
12	032	Banco Caja Social	1	T
13	040	Banco Agrario de Colombia	1	T
14	051	Bancamía	1	T
15	052	Banco WWB	1	T
16	053	Banco Falabella	1	T
17	054	Banco Pichincha	1	T
18	058	Banco ProCredit	1	T
19	059	Banco Mundo Mujer	1	T
20	060	Banco Finandina	1	T
21	061	Bancoomeva S.A.	1	T
22	062	Banco Davivienda	1	T
23	063	Banco Cooperativo Coopcentral	1	T
24	065	Banco Santander	1	T
25	101	Nequi	1	T
26	102	Daviplata	1	T
27	103	Movii	1	T
\.


--
-- TOC entry 5569 (class 0 OID 173195)
-- Dependencies: 245
-- Data for Name: colors; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.colors (id, name, hex_code, is_active, created_at, trial551) FROM stdin;
1	Blanco	#FFFFFF	1	2025-06-21 19:10:34	T
2	Negro	#000000	1	2025-06-21 19:10:34	T
3	Rojo	#FF0000	1	2025-06-21 19:10:34	T
4	Azul	#0000FF	1	2025-06-21 19:10:34	T
5	Azul Marino	#000080	1	2025-06-21 19:10:34	T
6	Azul Cielo	#87CEEB	1	2025-06-21 19:10:34	T
7	Rosado	#FFC0CB	1	2025-06-21 19:10:34	T
8	Rosado Pastel	#FFD1DC	1	2025-06-21 19:10:34	T
9	Morado	#800080	1	2025-06-21 19:10:34	T
10	Lila	#C8A2C8	1	2025-06-21 19:10:34	T
11	Amarillo	#FFFF00	1	2025-06-21 19:10:34	T
12	Amarillo Pastel	#FFFACD	1	2025-06-21 19:10:34	T
13	Verde	#008000	1	2025-06-21 19:10:34	T
14	Verde Mentha	#98FF98	1	2025-06-21 19:10:34	T
15	Naranja	#FFA500	1	2025-06-21 19:10:34	T
16	Melón	#FDBCB4	1	2025-06-21 19:10:34	T
17	Gris	#808080	1	2025-06-21 19:10:34	T
18	Beige	#F5F5DC	1	2025-06-21 19:10:34	T
19	Café	#A52A2A	1	2025-06-21 19:10:34	T
20	Estampado	\N	1	2025-06-21 19:10:34	T
\.


--
-- TOC entry 5571 (class 0 OID 173212)
-- Dependencies: 247
-- Data for Name: discount_code_products; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.discount_code_products (id, discount_code_id, product_id, created_at, trial551) FROM stdin;
\.


--
-- TOC entry 5573 (class 0 OID 173220)
-- Dependencies: 249
-- Data for Name: discount_code_usage; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.discount_code_usage (id, discount_code_id, user_id, order_id, used_at, trial551) FROM stdin;
\.


--
-- TOC entry 5575 (class 0 OID 173228)
-- Dependencies: 251
-- Data for Name: discount_codes; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.discount_codes (id, code, discount_type_id, discount_value, max_uses, used_count, start_date, end_date, is_active, is_single_use, created_by, created_at, updated_at, trial551) FROM stdin;
14	81B32E38	1	40.00	\N	0	\N	\N	0	0	6860007924a6a	2025-10-04 13:40:21	2025-11-25 20:56:39	T
15	E20FA9C5	1	20.00	\N	0	\N	\N	1	0	6860007924a6a	2025-10-04 15:41:22	2025-10-04 15:41:22	T
18	F3CE7C69	1	20.00	\N	0	\N	\N	1	0	6860007924a6a	2025-11-25 21:38:40	2025-11-25 21:38:40	T
19	DF95FF17	1	20.00	\N	0	\N	\N	1	1	6860007924a6a	2025-11-26 17:10:01	2025-11-26 17:10:01	T
\.


--
-- TOC entry 5577 (class 0 OID 173248)
-- Dependencies: 253
-- Data for Name: discount_types; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.discount_types (id, name, description, is_active, created_at, updated_at, trial551) FROM stdin;
1	Porcentaje	Descuento porcentual sobre el total	1	2025-07-27 17:38:50	2025-07-27 17:38:50	T
2	Monto fijo	Descuento de monto fijo	1	2025-07-27 17:38:50	2025-07-27 17:38:50	T
3	Envío gratis	Descuento para envío gratuito	1	2025-07-27 17:38:50	2025-07-27 17:38:50	T
\.


--
-- TOC entry 5579 (class 0 OID 173266)
-- Dependencies: 255
-- Data for Name: eliminaciones_auditoria; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.eliminaciones_auditoria (id, nombre, accion, fecha_eliminacion, trial551) FROM stdin;
\.


--
-- TOC entry 5581 (class 0 OID 173275)
-- Dependencies: 257
-- Data for Name: failed_jobs; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.failed_jobs (id, uuid, connection, queue, payload, exception, failed_at, trial551) FROM stdin;
\.


--
-- TOC entry 5583 (class 0 OID 173286)
-- Dependencies: 259
-- Data for Name: fixed_amount_discounts; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.fixed_amount_discounts (id, discount_code_id, amount, min_order_amount, trial551) FROM stdin;
\.


--
-- TOC entry 5585 (class 0 OID 173293)
-- Dependencies: 261
-- Data for Name: free_shipping_discounts; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.free_shipping_discounts (id, discount_code_id, shipping_method_id, trial551) FROM stdin;
\.


--
-- TOC entry 5587 (class 0 OID 173300)
-- Dependencies: 263
-- Data for Name: google_auth; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.google_auth (id, user_id, google_id, access_token, created_at, trial551) FROM stdin;
4	6860007924a6a	100021586628962750893	ya29.a0AS3H6NxWuxsKzvVZ78hlXIpNbkEuyBhlqCM-TAZVUOGequUWV3a07XgX6zOV1CBF5qW5qfR_7FaFucKHLluMZBfjTZw_MhKSVmbokJERwtzQbROc1a4BocIWIQ0ZL_W40z-KWYjh0I9SLbEtH3W2B_XqUlIn12l0fHRav4jESwaCgYKAZwSARESFQHGX2MihiMSealW_Ok6ItYSjWrP7Q0177	2025-07-09 18:18:26	T
5	68d315cf194ba	113148158052315120773	ya29.a0AQQ_BDTgZMejCPKvcenRcWzqFCDIVWn5qe70mTYR0Gl0z_BCBYe0xJKMW9pEAOlBNltLU6Fi4zZFIy9VYPy5b2y62ceUstxtrcyETYU_wdxnE3n-Pp0yhw4BTj8oNzZrsrOyCs2c7pDnAJCPzJdzpbtpU6dls4CNwilL9vfXt2cApsiw9vVNL8HYnEZflS8JlGtIht4aCgYKASUSARESFQHGX2Midxmzb7bnEUh1t_EFn8RQKw0206	2025-09-23 16:49:03	T
6	6860007924a6a	100021586628962750893	ya29.a0ATi6K2ty3GX5wGmPhWiuzIyNMrHsTbQ3Wh56g83kOAiPIPyDEaP8KK-TB5QtsD19w478mOYv7qFBQ5h6M8-OEjln2aWkL-O37cBN2o1r5hmln6Js98rQxFOWKteAl2UQjoAcABlHpUgGH7mVj5XY-7seetHZ2JaIDpUktZMBSDbIR-ux_0QtZlCCoaKAkYHf7YN1LhwaCgYKAbcSARESFQHGX2MiBW3sRvNNDtMOYh4v0hcHJg0206	2025-11-25 16:00:33	T
7	6860007924a6a	100021586628962750893	ya29.a0AUMWg_LTyDjJzZzGyHOvMA6BHeErQBrttxI0Xo1v1Irl-DwMOQrq7RkHKcxLihMS7NFqxqGtOaKdhT3zEc0O41t0Xt62vIya4QABOGA1Mdgav7pTERrKRljAb-ycQ3CxBfj4ypcHGDskn_mQxBeapiVHYTOsD6NCk27wk61AfPKm-wVSIVM7ZLLyFlx6kWoN0ti1tVMaCgYKAa0SARESFQHGX2MiUE2-Plu-L5pOD6B-1zDtnA0206	2026-01-24 12:02:39	T
\.


--
-- TOC entry 5588 (class 0 OID 173321)
-- Dependencies: 264
-- Data for Name: job_batches; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.job_batches (id, name, total_jobs, pending_jobs, failed_jobs, failed_job_ids, options, cancelled_at, created_at, finished_at, trial551) FROM stdin;
\.


--
-- TOC entry 5590 (class 0 OID 173329)
-- Dependencies: 266
-- Data for Name: jobs; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.jobs (id, queue, payload, attempts, reserved_at, available_at, created_at, trial551) FROM stdin;
\.


--
-- TOC entry 5592 (class 0 OID 173339)
-- Dependencies: 268
-- Data for Name: login_attempts; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.login_attempts (id, username, ip_address, attempt_date, trial551) FROM stdin;
1	braianoquen@gmail.com	::1	2025-07-20 11:18:27	T
2	3013636902	::1	2025-07-21 15:18:28	T
3	braianoquen@gmail.com	::1	2025-10-04 10:53:32	T
4	braianoquen@gmail.com	::1	2025-10-04 10:53:35	T
5	braianoquendurango@gmail.com	::1	2025-10-09 08:45:50	T
6	braianoquendurango@gmail.com	::1	2025-11-01 15:33:58	T
7	braianoquen@gmail.com	::1	2025-11-01 17:00:15	T
8	braianoquen2@gmail.com	::1	2025-11-01 18:41:12	T
9	braianoquen@gmail.com	::1	2025-11-01 18:42:18	T
10	braianoquendurango@gmail.com	::1	2025-11-07 06:15:10	T
11	braianoquen@example.com	127.0.0.1	2025-11-15 17:03:33	T
12	braianoquen@gmail.com	::1	2025-11-15 17:04:26	T
13	braianoquendurango@gmail.com	::1	2025-11-15 17:36:58	T
14	braianoquendurango@gmail.com	::1	2025-11-15 17:37:08	T
15	braianoquen@gmail.com	::1	2025-11-16 08:26:48	T
16	braianoquen@gmail.com	::1	2025-11-16 10:41:52	T
17	braianoquen@gmail.com	::1	2025-11-16 10:42:12	T
18	braianoquendurango@gmail.com	::1	2025-11-16 18:35:07	T
19	braianoquendurango@gmail.com	::1	2025-11-16 18:35:17	T
20	braianoquendurango@gmail.com	::1	2025-11-16 23:32:39	T
21	braianoquendurango@gmail.com	::1	2025-11-17 22:07:26	T
22	braianoquendurango@gmail.com	::1	2025-11-17 23:21:09	T
23	braianoquen@gmail.com	::1	2025-11-21 12:22:00	T
24	braianoquen@gmail.com	::1	2025-11-21 13:29:53	T
25	braianoquen@gmail.com	::1	2025-11-21 14:56:43	T
26	braianoquen@gmail.com	::1	2025-11-22 10:25:17	T
27	braianoquendurango@gmail.com	::1	2025-11-26 17:17:57	T
28	braianoquendurango@gmail.com	::1	2025-11-26 17:18:08	T
29	braianoquendurango@gmail.com	::1	2025-11-26 17:25:08	T
30	braianoquendurango@gmail.com	::1	2026-02-04 16:04:28	T
31	braianoquen@gmail.com	::1	2026-03-18 12:50:51	T
\.


--
-- TOC entry 5594 (class 0 OID 173355)
-- Dependencies: 270
-- Data for Name: migrations; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.migrations (id, migration, batch, trial551) FROM stdin;
1	0001_01_01_000000_create_users_table	1	T
2	0001_01_01_000001_create_cache_table	1	T
3	0001_01_01_000002_create_jobs_table	1	T
4	2025_11_14_000500_create_auth_support_tables	1	T
\.


--
-- TOC entry 5596 (class 0 OID 173370)
-- Dependencies: 272
-- Data for Name: notification_preferences; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.notification_preferences (id, user_id, type_id, email_enabled, sms_enabled, push_enabled, created_at, updated_at, trial551) FROM stdin;
1	6861e06ddcf49	2	1	0	1	2025-11-17 23:34:07	2025-11-17 23:38:52	T
2	6861e06ddcf49	3	1	0	1	2025-11-17 23:34:07	2025-11-17 23:38:52	T
3	6861e06ddcf49	1	1	0	1	2025-11-17 23:34:07	2025-11-17 23:38:52	T
\.


--
-- TOC entry 5598 (class 0 OID 173390)
-- Dependencies: 274
-- Data for Name: notification_queue; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.notification_queue (id, notification_id, channel, status, attempts, last_attempt_at, scheduled_at, sent_at, error_message, trial551) FROM stdin;
\.


--
-- TOC entry 5600 (class 0 OID 173402)
-- Dependencies: 276
-- Data for Name: notification_types; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.notification_types (id, name, description, template, is_active, created_at, updated_at, trial551) FROM stdin;
1	order	Notificaciones relacionadas con pedidos	Tu pedido #{order_id} ha cambiado de estado a: {status}	1	2025-11-12 08:37:38	2025-11-12 08:37:38	T
2	product	Notificaciones de productos (disponibilidad, nuevo stock)	El producto {product_name} está {status}	1	2025-11-12 08:37:38	2025-11-12 08:37:38	T
3	promotion	Ofertas y promociones especiales	Nueva promoción: {promotion_name}	1	2025-11-12 08:37:38	2025-11-12 08:37:38	T
4	account	Notificaciones de cuenta de usuario	Cambio en tu cuenta: {change_description}	1	2025-11-12 08:37:38	2025-11-12 08:37:38	T
5	system	Notificaciones del sistema	Mensaje del sistema: {message}	1	2025-11-12 08:37:38	2025-11-12 08:37:38	T
\.


--
-- TOC entry 5602 (class 0 OID 173426)
-- Dependencies: 278
-- Data for Name: notifications; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.notifications (id, user_id, type_id, title, message, related_entity_type, related_entity_id, is_read, is_email_sent, is_sms_sent, is_push_sent, expires_at, created_at, read_at, trial551) FROM stdin;
1	6861e06ddcf49	1	Pedido entregado #ORD20251116EAA8AB	Tu pedido #ORD20251116EAA8AB ha sido marcado como entregado. Encuentra tu factura adjunta en el correo.	order	7	1	0	0	0	\N	2025-11-16 12:55:47	2025-11-16 12:55:50	T
2	6861e06ddcf49	1	Tu envío está en camino	Tu pedido #ORD20251116EAA8AB ha salido para entrega. Pronto lo recibirás.	order	7	1	0	0	0	\N	2025-11-16 13:02:11	2025-11-16 13:02:18	T
3	6861e06ddcf49	1	Tu envío está en camino	Tu pedido #ORD202511163AF156 ha salido para entrega. Pronto lo recibirás.	order	8	1	0	0	0	\N	2025-11-16 20:01:57	2025-11-16 20:22:14	T
4	6861e06ddcf49	1	Pedido cancelado	Tu pedido #ORD202511163AF156 ha sido cancelado. Iniciaremos el reembolso en las próximas horas.	order	8	1	0	0	0	\N	2025-11-16 20:02:07	2025-11-16 20:22:14	T
5	6861e06ddcf49	1	Tu envío está en camino	Tu pedido #ORD202511163AF156 ha salido para entrega. Pronto lo recibirás.	order	8	1	0	0	0	\N	2025-11-16 20:02:51	2025-11-16 20:22:14	T
6	6861e06ddcf49	1	Pago aprobado	Hemos recibido el pago de tu pedido #ORD202511163AF156. Gracias.	order	8	1	0	0	0	\N	2025-11-16 20:02:57	2025-11-16 20:22:14	T
7	6861e06ddcf49	1	Pedido cancelado	Tu pedido #ORD202511163AF156 ha sido cancelado. Iniciaremos el reembolso en las próximas horas.	order	8	1	0	0	0	\N	2025-11-16 20:03:03	2025-11-16 20:22:14	T
8	6861e06ddcf49	1	Tu envío está en camino	Tu pedido #ORD202511163AF156 ha salido para entrega. Pronto lo recibirás.	order	8	1	0	0	0	\N	2025-11-16 20:03:54	2025-11-16 20:22:14	T
9	6861e06ddcf49	1	Pedido Confirmado	Tu pedido #1024 ha sido confirmado y está siendo preparado para envío.	order	1024	1	0	0	0	\N	2025-11-12 11:38:12	2025-11-12 08:51:10	T
10	6861e06ddcf49	1	Pedido en Camino	Tu pedido #1015 ha salido para entrega. Esperalo pronto.	order	1015	1	0	0	0	\N	2025-11-11 13:38:12	2025-11-11 14:38:12	T
11	6861e06ddcf49	1	Pedido Entregado	Tu pedido #1008 ha sido entregado exitosamente. ¡Gracias por tu compra!	order	1008	1	0	0	0	\N	2025-11-09 13:38:12	2025-11-09 15:38:12	T
12	6861e06ddcf49	2	Producto Disponible	¡Buenas noticias! El producto "Vestido Rosa Princesa" que agregaste a tu wishlist ya está disponible.	product	45	1	0	0	0	\N	2025-11-12 08:38:12	2025-11-12 08:51:10	T
13	6861e06ddcf49	2	Nuevo Stock	El producto "Pantalón Mezclilla Niño" ha vuelto a estar en stock.	product	32	1	0	0	0	\N	2025-11-11 13:38:12	2025-11-12 08:51:10	T
14	6861e06ddcf49	3	¡Oferta Especial del Fin de Semana!	Descuento del 30% en toda la colección primavera-verano. ¡No te lo pierdas!	promotion	5	1	0	0	0	\N	2025-11-12 10:38:12	2025-11-12 08:51:10	T
15	6861e06ddcf49	3	Cupón de Bienvenida	Usa el cupón BIENVENIDO20 para obtener 20% de descuento en tu próxima compra.	promotion	3	1	0	0	0	\N	2025-11-05 13:38:12	2025-11-06 13:38:12	T
16	6861e06ddcf49	4	Perfil Actualizado	Tu información de perfil ha sido actualizada correctamente.	account	\N	1	0	0	0	\N	2025-11-10 13:38:12	2025-11-10 14:08:12	T
17	6861e06ddcf49	4	Nueva Dirección Agregada	Se ha agregado una nueva dirección de envío a tu cuenta.	account	\N	1	0	0	0	\N	2025-11-12 07:38:12	2025-11-12 08:51:10	T
18	6861e06ddcf49	5	Actualización del Sistema	Hemos mejorado nuestra plataforma con nuevas funcionalidades. Descúbrelas ahora.	system	\N	1	0	0	0	\N	2025-11-07 13:38:12	2025-11-08 13:38:12	T
19	6861e06ddcf49	5	Bienvenido a Angelow	Gracias por registrarte. Explora nuestra colección de ropa infantil y descubre las mejores ofertas.	system	\N	1	0	0	0	\N	2025-11-02 13:38:12	2025-11-02 13:48:12	T
20	6861e06ddcf49	1	Pedido entregado #ORD202511163AF156	Tu pedido #ORD202511163AF156 ha sido marcado como entregado. Encuentra tu factura adjunta en el correo.	order	8	1	0	0	0	\N	2025-11-16 22:12:13	2025-11-16 22:13:31	T
21	6861e06ddcf49	1	Pago rechazado	Tu pago para el pedido #ORD202511163AF156 no fue aprobado. Revisa la referencia o contáctanos.	order	8	1	0	0	0	\N	2025-11-16 22:12:23	2025-11-16 22:13:31	T
22	6861000000	1	Pedido cancelado #ORD202511163AF156	Tu pedido #ORD202511163AF156 fue cancelado por el equipo de Angelow. El reembolso se procesará con el mismo método de pago y puede tardar entre 3 y 7 días hábiles.	order	8	0	0	0	0	\N	2025-11-16 22:13:09	\N	T
23	6861e06ddcf49	1	Pedido entregado #ORD202511163AF156	Tu pedido #ORD202511163AF156 ha sido marcado como entregado. Encuentra tu factura adjunta en el correo.	order	8	1	0	0	0	\N	2025-11-16 22:21:30	2025-11-16 22:24:57	T
24	6861000000	1	Pedido cancelado #ORD202511163AF156	Tu pedido #ORD202511163AF156 fue cancelado por el equipo de Angelow. El reembolso se procesará con el mismo método de pago y puede tardar entre 3 y 7 días hábiles.	order	8	0	0	0	0	\N	2025-11-16 22:21:55	\N	T
25	6861000000	1	Pedido cancelado #ORD202511163AF156	Tu pedido #ORD202511163AF156 fue cancelado por el equipo de Angelow. El reembolso se procesará con el mismo método de pago y puede tardar entre 3 y 7 días hábiles.	order	8	0	0	0	0	\N	2025-11-16 22:23:57	\N	T
26	6861e06ddcf49	1	Reembolso exitoso	Tu reembolso del pedido #ORD202511163AF156 fue acreditado con éxito. Dependiendo de tu banco, lo verás reflejado en 24-72 horas.	order	8	1	0	0	0	\N	2025-11-16 22:24:18	2025-11-16 22:24:57	T
27	6861e06ddcf49	1	Reembolso exitoso	Confirmamos el reembolso de tu pedido #ORD202511163AF156. Verás el dinero reflejado según los tiempos de tu banco.	order	8	1	0	0	0	\N	2025-11-16 22:24:18	2025-11-16 22:24:57	T
28	6861000000	1	Pedido cancelado #ORD202511163AF156	Tu pedido #ORD202511163AF156 fue cancelado por el equipo de Angelow. El reembolso se procesará con el mismo método de pago y puede tardar entre 3 y 7 días hábiles.	order	8	0	0	0	0	\N	2025-11-16 22:33:36	\N	T
29	6861e06ddcf49	1	Reembolso exitoso	Tu reembolso del pedido #ORD202511163AF156 fue acreditado con éxito. Dependiendo de tu banco, lo verás reflejado en 24-72 horas.	order	8	1	0	0	0	\N	2025-11-16 22:37:06	2025-11-16 23:01:47	T
30	6861e06ddcf49	1	Reembolso exitoso	Confirmamos el reembolso de tu pedido #ORD202511163AF156. Verás el dinero reflejado según los tiempos de tu banco.	order	8	1	0	0	0	\N	2025-11-16 22:37:06	2025-11-16 23:01:47	T
31	6861000000	1	Pedido cancelado #ORD202511163AF156	Tu pedido #ORD202511163AF156 fue cancelado por el equipo de Angelow. El reembolso se procesará con el mismo método de pago y puede tardar entre 3 y 7 días hábiles.	order	8	0	0	0	0	\N	2025-11-16 22:37:40	\N	T
32	6861e06ddcf49	1	Pedido entregado #ORD202511163AF156	Tu pedido #ORD202511163AF156 ha sido marcado como entregado. Encuentra tu factura adjunta en el correo.	order	8	1	0	0	0	\N	2025-11-16 22:38:48	2025-11-16 23:01:47	T
33	6861e06ddcf49	1	Pago aprobado	Hemos recibido el pago de tu pedido #ORD202511163AF156. Gracias.	order	8	1	0	0	0	\N	2025-11-16 22:58:40	2025-11-16 23:01:47	T
34	6861e06ddcf49	1	Pago rechazado	Tu pago para el pedido #ORD202511163AF156 no fue aprobado. Revisa la referencia o contáctanos.	order	8	1	0	0	0	\N	2025-11-16 23:01:19	2025-11-16 23:01:47	T
35	6861e06ddcf49	1	Pago aprobado	Hemos recibido el pago de tu pedido #ORD202511163AF156. Gracias.	order	8	1	0	0	0	\N	2025-11-16 23:01:27	2025-11-16 23:01:47	T
36	6861000000	1	Pedido cancelado #ORD202511160CB9A1	Hemos confirmado la cancelación del pedido #ORD202511160CB9A1 que solicitaste. El reembolso se procesará con el mismo método de pago y puede tardar entre 3 y 7 días hábiles.	order	6	0	0	0	0	\N	2025-11-16 23:11:45	\N	T
37	6861e06ddcf49	1	Reembolso exitoso	Tu reembolso del pedido #ORD202511160CB9A1 fue acreditado con éxito. Dependiendo de tu banco, lo verás reflejado en 24-72 horas.	order	6	1	0	0	0	\N	2025-11-16 23:12:44	2025-11-17 08:26:59	T
38	6861e06ddcf49	1	Reembolso exitoso	Confirmamos el reembolso de tu pedido #ORD202511160CB9A1. Verás el dinero reflejado según los tiempos de tu banco.	order	6	1	0	0	0	\N	2025-11-16 23:12:44	2025-11-17 08:26:59	T
39	6861e06ddcf49	1	Pedido entregado #ORD20251116448A7E	Tu pedido #ORD20251116448A7E ha sido marcado como entregado. Encuentra tu factura adjunta en el correo.	order	5	1	0	0	0	\N	2025-11-17 13:04:12	2025-11-17 22:31:06	T
40	6861e06ddcf49	1	Pago aprobado	Hemos recibido el pago de tu pedido #ORD20251116134F34. Gracias.	order	4	1	0	0	0	\N	2025-11-17 13:05:02	2025-11-17 22:31:06	T
41	6860007924a6a	3	Oferta: Ropa deportiva - 22% OFF	¡Ropa deportiva ahora por $35.000 (antes $45.000) — 22% de descuento!	promotion	71	0	0	0	0	\N	2025-11-17 23:20:44	\N	T
42	6861e06ddcf49	3	Oferta: Ropa deportiva - 22% OFF	¡Ropa deportiva ahora por $35.000 (antes $45.000) — 22% de descuento!	promotion	71	1	0	0	0	\N	2025-11-17 23:20:44	2025-11-17 23:20:49	T
43	6861e06ddcf49	1	Pedido entregado #ORD20251116EAA8AB	Tu pedido #ORD20251116EAA8AB ha sido marcado como entregado. Encuentra tu factura adjunta en el correo.	order	7	1	0	0	0	\N	2025-11-22 22:58:46	2025-11-25 21:47:07	T
44	6861e06ddcf49	1	Pedido entregado #ORD20251116134F34	Tu pedido #ORD20251116134F34 ha sido marcado como entregado. Encuentra tu factura adjunta en el correo.	order	4	1	0	0	0	\N	2025-11-22 22:59:06	2025-11-25 21:47:07	T
45	6861e06ddcf49	1	Pago aprobado	Hemos recibido el pago de tu pedido #ORD20251116448A7E. Gracias.	order	5	1	0	0	0	\N	2025-11-22 22:59:25	2025-11-25 21:47:07	T
46	6861e06ddcf49	1	Pedido entregado #ORD2025111677ADAB	Tu pedido #ORD2025111677ADAB ha sido marcado como entregado. Encuentra tu factura adjunta en el correo.	order	3	1	0	0	0	\N	2025-11-22 23:00:00	2025-11-25 21:47:07	T
47	6861e06ddcf49	1	Pedido entregado #ORD20251115C9DBC9	Tu pedido #ORD20251115C9DBC9 ha sido marcado como entregado. Encuentra tu factura adjunta en el correo.	order	2	1	0	0	0	\N	2025-11-22 23:00:14	2025-11-25 21:47:07	T
48	6861e06ddcf49	1	Pedido entregado #TEST37D9B8	Tu pedido #TEST37D9B8 ha sido marcado como entregado. Encuentra tu factura adjunta en el correo.	order	1	1	0	0	0	\N	2025-11-22 23:00:25	2025-11-25 21:47:07	T
49	6861e06ddcf49	1	Pago aprobado	Hemos recibido el pago de tu pedido #ORD2025111677ADAB. Gracias.	order	3	1	0	0	0	\N	2025-11-22 23:00:38	2025-11-25 21:47:07	T
50	6861e06ddcf49	1	Pago aprobado	Hemos recibido el pago de tu pedido #ORD20251115C9DBC9. Gracias.	order	2	1	0	0	0	\N	2025-11-22 23:00:50	2025-11-25 21:47:07	T
51	6861e06ddcf49	1	Pago aprobado	Hemos recibido el pago de tu pedido #TEST37D9B8. Gracias.	order	1	1	0	0	0	\N	2025-11-22 23:01:03	2025-11-25 21:47:07	T
52	6861e06ddcf49	1	Pago aprobado	Hemos recibido el pago de tu pedido #ORD2025112208851F. Gracias.	order	9	1	0	0	0	\N	2025-11-22 23:09:00	2025-11-25 21:47:07	T
53	6861e06ddcf49	1	Pago aprobado	Hemos recibido el pago de tu pedido #ORD2025112208851F. Gracias.	order	9	1	0	0	0	\N	2025-11-22 23:15:18	2025-11-25 21:47:07	T
54	6861e06ddcf49	1	Tu envío está en camino	Tu pedido #ORD2025112208851F ha salido para entrega. Pronto lo recibirás.	order	9	1	0	0	0	\N	2025-11-22 23:21:01	2025-11-25 21:47:07	T
55	6861e06ddcf49	1	Pago aprobado	Hemos recibido el pago de tu pedido #ORD2025112335EAC5. Gracias.	order	11	1	0	0	0	\N	2025-11-23 00:06:18	2025-11-25 21:47:07	T
56	6861e06ddcf49	1	Tu envío está en camino	Tu pedido #ORD2025112335EAC5 ha salido para entrega. Pronto lo recibirás.	order	11	1	0	0	0	\N	2025-11-23 00:06:23	2025-11-25 21:47:07	T
57	6861e06ddcf49	1	Pago aprobado	Hemos recibido el pago de tu pedido #ORD202511255DCC59. Gracias.	order	17	0	0	0	0	\N	2025-11-25 23:22:34	\N	T
58	6861e06ddcf49	1	Pedido en proceso	Tu pedido #ORD202511255DCC59 está en proceso y pronto saldrá para entrega.	order	17	0	0	0	0	\N	2025-11-25 23:22:42	\N	T
59	6861e06ddcf49	1	Tu envío está en camino	Tu pedido #ORD202511255DCC59 ha salido para entrega. Pronto lo recibirás.	order	17	0	0	0	0	\N	2025-11-25 23:23:20	\N	T
60	6861e06ddcf49	1	Pedido entregado #ORD202511255DCC59	Tu pedido #ORD202511255DCC59 ha sido marcado como entregado. Encuentra tu factura adjunta en el correo.	order	17	0	0	0	0	\N	2025-11-25 23:23:55	\N	T
61	6861e06ddcf49	1	Pago aprobado	Hemos recibido el pago de tu pedido #ORD20251125AA48F4. Gracias.	order	16	0	0	0	0	\N	2025-11-25 23:26:54	\N	T
62	6861e06ddcf49	1	Pedido entregado #ORD20251125AA48F4	Tu pedido #ORD20251125AA48F4 ha sido marcado como entregado. Encuentra tu factura adjunta en el correo.	order	16	0	0	0	0	\N	2025-11-25 23:27:11	\N	T
63	6861000000	1	Pedido cancelado #ORD20251125FB1BE6	Hemos confirmado la cancelación del pedido #ORD20251125FB1BE6 que solicitaste. El reembolso se procesará con el mismo método de pago y puede tardar entre 3 y 7 días hábiles.	order	15	0	0	0	0	\N	2025-11-26 00:11:24	\N	T
64	6861000000	1	Pedido cancelado #ORD2025112501BD10	Hemos confirmado la cancelación del pedido #ORD2025112501BD10 que solicitaste. El reembolso se procesará con el mismo método de pago y puede tardar entre 3 y 7 días hábiles.	order	13	0	0	0	0	\N	2025-11-26 00:17:11	\N	T
65	6861000000	1	Pedido cancelado #ORD20251125EDC719	Tu pedido #ORD20251125EDC719 fue cancelado por el equipo de Angelow. El reembolso se procesará con el mismo método de pago y puede tardar entre 3 y 7 días hábiles.	order	12	0	0	0	0	\N	2025-11-26 00:23:38	\N	T
66	6861e06ddcf49	1	Reembolso exitoso	Tu reembolso del pedido #ORD20251125FB1BE6 fue acreditado con éxito. Dependiendo de tu banco, lo verás reflejado en 24-72 horas.	order	15	0	0	0	0	\N	2025-11-26 00:24:10	\N	T
67	6861e06ddcf49	1	Reembolso exitoso	Confirmamos el reembolso de tu pedido #ORD20251125FB1BE6. Verás el dinero reflejado según los tiempos de tu banco.	order	15	0	0	0	0	\N	2025-11-26 00:24:10	\N	T
68	6861e06ddcf49	1	Pago aprobado	Hemos recibido el pago de tu pedido #ORD202511263E6FF6. Gracias.	order	19	0	0	0	0	\N	2025-11-26 17:20:42	\N	T
69	6861e06ddcf49	1	Pedido en proceso	Tu pedido #ORD202511263E6FF6 está en proceso y pronto saldrá para entrega.	order	19	0	0	0	0	\N	2025-11-26 17:20:51	\N	T
70	6861e06ddcf49	1	Pedido entregado #ORD202511263E6FF6	Tu pedido #ORD202511263E6FF6 ha sido marcado como entregado. Encuentra tu factura adjunta en el correo.	order	19	0	0	0	0	\N	2025-11-26 17:21:49	\N	T
\.


--
-- TOC entry 5604 (class 0 OID 173452)
-- Dependencies: 280
-- Data for Name: order_items; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.order_items (id, order_id, product_id, color_variant_id, size_variant_id, product_name, variant_name, price, quantity, total, created_at, trial551) FROM stdin;
1	1	71	16	26	Ropa deportiva		35000.00	1	35000.00	2025-11-15 22:10:11	T
2	2	71	16	26	Ropa deportiva	Color: Negro - Talla: XS	35000.00	1	35000.00	2025-11-15 22:17:00	T
3	3	71	16	26	Ropa deportiva	Color: Negro - Talla: XS	35000.00	1	35000.00	2025-11-16 09:09:59	T
4	4	71	16	26	Ropa deportiva	Color: Negro - Talla: XS	35000.00	1	35000.00	2025-11-16 09:12:17	T
5	5	71	16	26	Ropa deportiva	Color: Negro - Talla: XS	35000.00	1	35000.00	2025-11-16 09:16:36	T
7	7	71	16	26	Ropa deportiva	Color: Negro - Talla: XS	35000.00	1	35000.00	2025-11-16 09:54:06	T
8	8	71	16	26	Ropa deportiva	Color: Negro - Talla: XS	35000.00	1	35000.00	2025-11-16 11:58:11	T
9	9	71	28	37	Ropa deportiva	Color: Beige - Talla: S	45000.00	5	225000.00	2025-11-22 23:07:44	T
10	9	71	27	36	Ropa deportiva	Color: Negro - Talla: XS	35000.00	10	350000.00	2025-11-22 23:07:44	T
11	10	75	32	44	conjunto elegante	Color: Blanco - Talla: M	50000.00	7	350000.00	2025-11-22 23:54:53	T
12	11	73	30	41	Ropa de baño 	Color: Verde Mentha - Talla: XS	50000.00	6	300000.00	2025-11-23 00:04:19	T
13	12	73	30	41	Ropa de baño 	Color: Verde Mentha - Talla: XS	50000.00	2	100000.00	2025-11-25 22:09:34	T
14	13	71	34	46	Ropa deportiva	Color: Beige - Talla: S	45000.00	1	45000.00	2025-11-25 22:43:44	T
15	14	71	36	48	Ropa deportiva	Color: Beige - Talla: S	45000.00	1	45000.00	2025-11-25 23:05:14	T
16	15	71	36	48	Ropa deportiva	Color: Beige - Talla: S	45000.00	2	90000.00	2025-11-25 23:09:51	T
17	16	71	36	48	Ropa deportiva	Color: Beige - Talla: S	45000.00	1	45000.00	2025-11-25 23:11:22	T
18	16	71	35	47	Ropa deportiva	Color: Negro - Talla: XS	35000.00	1	35000.00	2025-11-25 23:11:22	T
19	17	71	36	48	Ropa deportiva	Color: Beige - Talla: S	45000.00	1	45000.00	2025-11-25 23:20:37	T
20	17	71	35	47	Ropa deportiva	Color: Negro - Talla: XS	35000.00	1	35000.00	2025-11-25 23:20:37	T
21	18	71	36	48	Ropa deportiva	Color: Beige - Talla: S	45000.00	1	45000.00	2025-11-26 17:14:59	T
22	18	71	35	47	Ropa deportiva	Color: Negro - Talla: XS	35000.00	18	630000.00	2025-11-26 17:14:59	T
23	19	71	36	48	Ropa deportiva	Color: Beige - Talla: S	45000.00	1	45000.00	2025-11-26 17:19:31	T
24	19	71	35	47	Ropa deportiva	Color: Negro - Talla: XS	35000.00	1	35000.00	2025-11-26 17:19:31	T
\.


--
-- TOC entry 5606 (class 0 OID 173474)
-- Dependencies: 282
-- Data for Name: order_status_history; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.order_status_history (id, order_id, changed_by, changed_by_name, change_type, field_changed, old_value, new_value, description, ip_address, user_agent, created_at, trial551) FROM stdin;
1	1	6861e06ddcf49	Cliente	created	order_created	\N	TEST37D9B8	Orden #TEST37D9B8 creada con total de $45,000	\N	\N	2025-11-15 22:10:11	T
2	2	6861e06ddcf49	Cliente	created	order_created	\N	ORD20251115C9DBC9	Orden #ORD20251115C9DBC9 creada con total de $36,000	\N	\N	2025-11-15 22:17:00	T
3	3	6861e06ddcf49	Cliente	created	order_created	\N	ORD2025111677ADAB	Orden #ORD2025111677ADAB creada con total de $35,000	\N	\N	2025-11-16 09:09:59	T
4	4	6861e06ddcf49	Cliente	created	order_created	\N	ORD20251116134F34	Orden #ORD20251116134F34 creada con total de $35,000	\N	\N	2025-11-16 09:12:17	T
5	5	6861e06ddcf49	Cliente	created	order_created	\N	ORD20251116448A7E	Orden #ORD20251116448A7E creada con total de $35,000	\N	\N	2025-11-16 09:16:36	T
6	6	6861e06ddcf49	Cliente	created	order_created	\N	ORD202511160CB9A1	Orden #ORD202511160CB9A1 creada con total de $35,000	\N	\N	2025-11-16 09:46:56	T
7	7	6861e06ddcf49	Cliente	created	order_created	\N	ORD20251116EAA8AB	Orden #ORD20251116EAA8AB creada con total de $35,000	\N	\N	2025-11-16 09:54:06	T
8	7	6860007924a6a	Braian	status	status	pending	processing	Estado cambiado de "Pendiente" a "En proceso"	127.0.0.1 (localhost)	\N	2025-11-16 10:55:10	T
9	7	6860007924a6a	Braian	payment_status	payment_status	pending	paid	Estado de pago cambiado de "Pendiente" a "Pagado"	::1	\N	2025-11-16 11:09:31	T
10	7	6860007924a6a	Braian	payment_status	payment_status	pending	paid	Cambio de estado de pago	::1	\N	2025-11-16 11:09:31	T
11	7	6860007924a6a	Braian	status	status	processing	shipped	Estado cambiado de "En proceso" a "Enviado"	127.0.0.1 (localhost)	\N	2025-11-16 11:49:52	T
12	8	6861e06ddcf49	Cliente	created	order_created	\N	ORD202511163AF156	Orden #ORD202511163AF156 creada con total de $43,000	\N	\N	2025-11-16 11:58:11	T
13	7	6860007924a6a	Braian	status	status	shipped	delivered	Estado cambiado de "Enviado" a "Entregado"	127.0.0.1 (localhost)	\N	2025-11-16 12:13:02	T
14	7	6860007924a6a	Braian	status	status	delivered	shipped	Estado cambiado de "Entregado" a "Enviado"	127.0.0.1 (localhost)	\N	2025-11-16 12:13:24	T
15	7	6860007924a6a	Braian	status	status	shipped	delivered	Estado cambiado de "Enviado" a "Entregado"	127.0.0.1 (localhost)	\N	2025-11-16 12:13:38	T
16	7	6860007924a6a	Braian	status	status	delivered	shipped	Estado cambiado de "Entregado" a "Enviado"	127.0.0.1 (localhost)	\N	2025-11-16 12:16:59	T
17	7	6860007924a6a	Braian	status	status	shipped	delivered	Estado cambiado de "Enviado" a "Entregado"	127.0.0.1 (localhost)	\N	2025-11-16 12:17:05	T
18	7	6860007924a6a	Braian	status	status	delivered	shipped	Estado cambiado de "Entregado" a "Enviado"	127.0.0.1 (localhost)	\N	2025-11-16 12:21:58	T
19	7	6860007924a6a	Braian	status	status	shipped	delivered	Estado cambiado de "Enviado" a "Entregado"	127.0.0.1 (localhost)	\N	2025-11-16 12:22:07	T
20	7	6860007924a6a	Braian	status	status	delivered	shipped	Estado cambiado de "Entregado" a "Enviado"	127.0.0.1 (localhost)	\N	2025-11-16 12:28:18	T
21	7	6860007924a6a	Braian	status	status	shipped	delivered	Estado cambiado de "Enviado" a "Entregado"	127.0.0.1 (localhost)	\N	2025-11-16 12:28:24	T
22	7	6860007924a6a	Braian	status	status	delivered	shipped	Estado cambiado de "Entregado" a "Enviado"	127.0.0.1 (localhost)	\N	2025-11-16 12:33:16	T
23	7	6860007924a6a	Braian	status	status	shipped	delivered	Estado cambiado de "Enviado" a "Entregado"	127.0.0.1 (localhost)	\N	2025-11-16 12:33:22	T
24	7	6860007924a6a	Braian	status	status	delivered	shipped	Estado cambiado de "Entregado" a "Enviado"	127.0.0.1 (localhost)	\N	2025-11-16 12:36:17	T
25	7	6860007924a6a	Braian	status	status	shipped	delivered	Estado cambiado de "Enviado" a "Entregado"	127.0.0.1 (localhost)	\N	2025-11-16 12:36:22	T
26	7	6860007924a6a	Braian	status	status	delivered	shipped	Estado cambiado de "Entregado" a "Enviado"	127.0.0.1 (localhost)	\N	2025-11-16 12:42:53	T
27	7	6860007924a6a	Braian	status	status	shipped	delivered	Estado cambiado de "Enviado" a "Entregado"	127.0.0.1 (localhost)	\N	2025-11-16 12:43:00	T
28	7	6860007924a6a	Braian	status	status	delivered	shipped	Estado cambiado de "Entregado" a "Enviado"	127.0.0.1 (localhost)	\N	2025-11-16 12:45:57	T
29	7	6860007924a6a	Braian	status	status	shipped	delivered	Estado cambiado de "Enviado" a "Entregado"	127.0.0.1 (localhost)	\N	2025-11-16 12:46:22	T
30	7	6860007924a6a	Braian	status	status	delivered	shipped	Estado cambiado de "Entregado" a "Enviado"	127.0.0.1 (localhost)	\N	2025-11-16 12:48:44	T
31	7	6860007924a6a	Braian	status	status	shipped	delivered	Estado cambiado de "Enviado" a "Entregado"	127.0.0.1 (localhost)	\N	2025-11-16 12:48:49	T
32	7	6860007924a6a	Braian	status	status	delivered	shipped	Estado cambiado de "Entregado" a "Enviado"	127.0.0.1 (localhost)	\N	2025-11-16 12:49:19	T
33	7	6860007924a6a	Braian	status	status	shipped	delivered	Estado cambiado de "Enviado" a "Entregado"	127.0.0.1 (localhost)	\N	2025-11-16 12:49:24	T
34	7	6860007924a6a	Braian	status	status	delivered	shipped	Estado cambiado de "Entregado" a "Enviado"	127.0.0.1 (localhost)	\N	2025-11-16 12:50:15	T
35	7	6860007924a6a	Braian	status	status	shipped	delivered	Estado cambiado de "Enviado" a "Entregado"	127.0.0.1 (localhost)	\N	2025-11-16 12:50:21	T
36	7	6860007924a6a	Braian	status	status	delivered	shipped	Estado cambiado de "Entregado" a "Enviado"	127.0.0.1 (localhost)	\N	2025-11-16 12:51:37	T
37	7	6860007924a6a	Braian	status	status	shipped	delivered	Estado cambiado de "Enviado" a "Entregado"	127.0.0.1 (localhost)	\N	2025-11-16 12:51:42	T
38	7	6860007924a6a	Braian	status	status	delivered	shipped	Estado cambiado de "Entregado" a "Enviado"	127.0.0.1 (localhost)	\N	2025-11-16 12:55:36	T
39	7	6860007924a6a	Braian	status	status	shipped	delivered	Estado cambiado de "Enviado" a "Entregado"	127.0.0.1 (localhost)	\N	2025-11-16 12:55:41	T
40	7	6860007924a6a	Braian	status	status	delivered	shipped	Estado cambiado de "Entregado" a "Enviado"	127.0.0.1 (localhost)	\N	2025-11-16 13:02:11	T
41	8	\N	Sistema	status	status	pending	cancelled	Estado cambiado de "Pendiente" a "Cancelado"	\N	\N	2025-11-16 18:53:06	T
42	8	6860007924a6a	Braian	status	status	cancelled	shipped	Estado cambiado de "Cancelado" a "Enviado"	127.0.0.1 (localhost)	\N	2025-11-16 20:01:57	T
43	8	6860007924a6a	Braian	status	status	shipped	cancelled	Estado cambiado de "Enviado" a "Cancelado"	127.0.0.1 (localhost)	\N	2025-11-16 20:02:07	T
44	8	6860007924a6a	Braian	status	status	cancelled	shipped	Estado cambiado de "Cancelado" a "Enviado"	127.0.0.1 (localhost)	\N	2025-11-16 20:02:51	T
45	8	6860007924a6a	Braian	payment_status	payment_status	pending	paid	Estado de pago cambiado de "Pendiente" a "Pagado"	::1	\N	2025-11-16 20:02:57	T
46	8	6860007924a6a	Braian	payment_status	payment_status	pending	paid	Cambio de estado de pago	::1	\N	2025-11-16 20:02:57	T
47	8	6860007924a6a	Braian	status	status	shipped	cancelled	Estado cambiado de "Enviado" a "Cancelado"	127.0.0.1 (localhost)	\N	2025-11-16 20:03:03	T
48	8	6860007924a6a	Braian	status	status	cancelled	shipped	Estado cambiado de "Cancelado" a "Enviado"	127.0.0.1 (localhost)	\N	2025-11-16 20:03:54	T
49	8	6860007924a6a	Braian	payment_status	payment_status	paid	pending	Estado de pago cambiado de "Pagado" a "Pendiente"	::1	\N	2025-11-16 20:12:20	T
50	8	6860007924a6a	Braian	payment_status	payment_status	paid	pending	Cambio de estado de pago	::1	\N	2025-11-16 20:12:20	T
51	8	6860007924a6a	Braian	payment_status	payment_status	pending	paid	Estado de pago cambiado de "Pendiente" a "Pagado"	::1	\N	2025-11-16 20:12:49	T
52	8	6860007924a6a	Braian	payment_status	payment_status	pending	paid	Cambio de estado de pago	::1	\N	2025-11-16 20:12:49	T
53	8	6860007924a6a	Braian	status	status	shipped	cancelled	Estado cambiado de "Enviado" a "Cancelado"	127.0.0.1 (localhost)	\N	2025-11-16 20:12:56	T
54	8	6860007924a6a	Braian	payment_status	payment_status	paid	refunded	Estado de pago cambiado de "Pagado" a "Reembolsado"	127.0.0.1 (localhost)	\N	2025-11-16 20:12:56	T
55	8	6860007924a6a	Braian	status	status	cancelled	refunded	Estado cambiado de "Cancelado" a "Reembolsado"	127.0.0.1 (localhost)	\N	2025-11-16 22:00:43	T
56	8	6860007924a6a	Braian	payment_status	payment_status	refunded	paid	Estado de pago cambiado de "Reembolsado" a "Pagado"	::1	\N	2025-11-16 22:03:56	T
57	8	6860007924a6a	Braian	payment_status	payment_status	refunded	paid	Cambio de estado de pago	::1	\N	2025-11-16 22:03:56	T
58	8	6860007924a6a	Braian	status	status	refunded	processing	Estado cambiado de "Reembolsado" a "En proceso"	127.0.0.1 (localhost)	\N	2025-11-16 22:04:07	T
59	8	6860007924a6a	Braian	payment_status	payment_status	paid	refunded	Estado de pago cambiado de "Pagado" a "Reembolsado"	::1	\N	2025-11-16 22:04:12	T
60	8	6860007924a6a	Braian	payment_status	payment_status	paid	refunded	Cambio de estado de pago	::1	\N	2025-11-16 22:04:12	T
61	8	6860007924a6a	Braian	status	status	processing	refunded	Estado cambiado de "En proceso" a "Reembolsado"	127.0.0.1 (localhost)	\N	2025-11-16 22:04:30	T
62	8	6860007924a6a	Braian	payment_status	payment_status	refunded	paid	Estado de pago cambiado de "Reembolsado" a "Pagado"	::1	\N	2025-11-16 22:06:58	T
63	8	6860007924a6a	Braian	payment_status	payment_status	refunded	paid	Cambio de estado de pago	::1	\N	2025-11-16 22:06:58	T
64	8	6860007924a6a	Braian	status	status	refunded	cancelled	Estado cambiado de "Reembolsado" a "Cancelado"	127.0.0.1 (localhost)	\N	2025-11-16 22:07:02	T
65	8	6860007924a6a	Braian	payment_status	payment_status	paid	refunded	Estado de pago cambiado de "Pagado" a "Reembolsado"	127.0.0.1 (localhost)	\N	2025-11-16 22:07:02	T
66	8	6860007924a6a	Braian	status	status	cancelled	refunded	Estado cambiado de "Cancelado" a "Reembolsado"	127.0.0.1 (localhost)	\N	2025-11-16 22:07:14	T
67	8	6860007924a6a	Braian	status	status	refunded	cancelled	Estado cambiado de "Reembolsado" a "Cancelado"	127.0.0.1 (localhost)	\N	2025-11-16 22:09:33	T
68	8	6860007924a6a	Braian	status	status	cancelled	refunded	Estado cambiado de "Cancelado" a "Reembolsado"	127.0.0.1 (localhost)	\N	2025-11-16 22:09:43	T
69	8	6860007924a6a	Braian	payment_status	payment_status	refunded	paid	Estado de pago cambiado de "Reembolsado" a "Pagado"	::1	\N	2025-11-16 22:12:03	T
70	8	6860007924a6a	Braian	payment_status	payment_status	refunded	paid	Cambio de estado de pago	::1	\N	2025-11-16 22:12:03	T
71	8	6860007924a6a	Braian	status	status	refunded	delivered	Estado cambiado de "Reembolsado" a "Entregado"	127.0.0.1 (localhost)	\N	2025-11-16 22:12:09	T
72	8	6860007924a6a	Braian	payment_status	payment_status	paid	failed	Estado de pago cambiado de "Pagado" a "Fallido"	::1	\N	2025-11-16 22:12:23	T
73	8	6860007924a6a	Braian	payment_status	payment_status	paid	failed	Cambio de estado de pago	::1	\N	2025-11-16 22:12:23	T
74	8	6860007924a6a	Braian	status	status	delivered	cancelled	Estado cambiado de "Entregado" a "Cancelado"	127.0.0.1 (localhost)	\N	2025-11-16 22:13:09	T
75	8	6860007924a6a	Braian	status	status	cancelled	refunded	Estado cambiado de "Cancelado" a "Reembolsado"	127.0.0.1 (localhost)	\N	2025-11-16 22:13:17	T
76	8	6860007924a6a	Braian	payment_status	payment_status	failed	refunded	Estado de pago cambiado de "Fallido" a "Reembolsado"	127.0.0.1 (localhost)	\N	2025-11-16 22:13:17	T
77	8	6860007924a6a	Braian	status	status	refunded	delivered	Estado cambiado de "Reembolsado" a "Entregado"	127.0.0.1 (localhost)	\N	2025-11-16 22:21:19	T
78	8	6860007924a6a	Braian	status	status	delivered	refunded	Estado cambiado de "Entregado" a "Reembolsado"	127.0.0.1 (localhost)	\N	2025-11-16 22:21:36	T
79	8	6860007924a6a	Braian	status	status	refunded	cancelled	Estado cambiado de "Reembolsado" a "Cancelado"	127.0.0.1 (localhost)	\N	2025-11-16 22:21:55	T
80	8	6860007924a6a	Braian	status	status	cancelled	refunded	Estado cambiado de "Cancelado" a "Reembolsado"	127.0.0.1 (localhost)	\N	2025-11-16 22:22:09	T
81	8	6860007924a6a	Braian	status	status	refunded	cancelled	Estado cambiado de "Reembolsado" a "Cancelado"	127.0.0.1 (localhost)	\N	2025-11-16 22:23:57	T
82	8	6860007924a6a	Braian	status	status	cancelled	refunded	Estado cambiado de "Cancelado" a "Reembolsado"	127.0.0.1 (localhost)	\N	2025-11-16 22:24:11	T
83	8	6860007924	6860007924	refunded	payment_status	43000	refunded	Reembolso de 43.000 registrado en sistema. Ref: 21212121	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0	2025-11-16 22:24:11	T
84	8	6860007924a6a	Braian	status	status	refunded	cancelled	Estado cambiado de "Reembolsado" a "Cancelado"	127.0.0.1 (localhost)	\N	2025-11-16 22:33:36	T
85	8	6860007924a6a	Braian	status	status	cancelled	refunded	Estado cambiado de "Cancelado" a "Reembolsado"	127.0.0.1 (localhost)	\N	2025-11-16 22:37:00	T
86	8	6860007924a6a	Braian	status	status	refunded	cancelled	Estado cambiado de "Reembolsado" a "Cancelado"	127.0.0.1 (localhost)	\N	2025-11-16 22:37:40	T
87	8	6860007924a6a	Braian	status	status	cancelled	delivered	Estado cambiado de "Cancelado" a "Entregado"	127.0.0.1 (localhost)	\N	2025-11-16 22:38:38	T
88	8	6860007924a6a	Braian	payment_status	payment_status	refunded	paid	Estado de pago actualizado	::1	\N	2025-11-16 22:58:40	T
89	8	6860007924a6a	Braian	payment_status	payment_status	refunded	paid	Cambio de estado de pago	::1	\N	2025-11-16 22:58:40	T
90	8	6860007924a6a	Braian	payment_status	payment_status	paid	failed	Estado de pago actualizado	::1	\N	2025-11-16 23:01:19	T
91	8	6860007924a6a	Braian	payment_status	payment_status	paid	failed	Cambio de estado de pago	::1	\N	2025-11-16 23:01:19	T
92	8	6860007924a6a	Braian	payment_status	payment_status	failed	paid	Estado de pago actualizado	::1	\N	2025-11-16 23:01:27	T
93	8	6860007924a6a	Braian	payment_status	payment_status	failed	paid	Cambio de estado de pago	::1	\N	2025-11-16 23:01:27	T
94	6	\N	Sistema	status	status	pending	cancelled	Estado cambiado: pending ÔåÆ cancelled	\N	\N	2025-11-16 23:11:45	T
95	6	\N	Sistema	payment_status	payment_status	pending	refunded	Estado de pago actualizado	\N	\N	2025-11-16 23:11:45	T
96	6	6860007924a6a	Braian	status	status	cancelled	refunded	Estado cambiado: cancelled ÔåÆ refunded	127.0.0.1 (localhost)	\N	2025-11-16 23:12:38	T
97	6	6860007924	6860007924	refunded	payment_status	35000	refunded	Reembolso de 35.000 registrado en sistema. Ref: 454543432323	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0	2025-11-16 23:12:38	T
98	5	6860007924a6a	Braian	status	status	pending	delivered	Estado cambiado: pending ÔåÆ delivered	127.0.0.1 (localhost)	\N	2025-11-17 13:04:02	T
99	4	6860007924a6a	Braian	payment_status	payment_status	pending	paid	Estado de pago actualizado	::1	\N	2025-11-17 13:05:02	T
100	4	6860007924a6a	Braian	payment_status	payment_status	pending	paid	Cambio de estado de pago	::1	\N	2025-11-17 13:05:02	T
101	7	6860007924a6a	Braian	status	status	shipped	delivered	Estado cambiado: shipped ÔåÆ delivered	127.0.0.1 (localhost)	\N	2025-11-22 22:58:30	T
102	4	6860007924a6a	Braian	status	status	pending	delivered	Estado cambiado: pending ÔåÆ delivered	127.0.0.1 (localhost)	\N	2025-11-22 22:58:52	T
103	5	6860007924a6a	Braian	payment_status	payment_status	pending	paid	Estado de pago actualizado	::1	\N	2025-11-22 22:59:25	T
104	5	6860007924a6a	Braian	payment_status	payment_status	pending	paid	Cambio de estado de pago	::1	\N	2025-11-22 22:59:25	T
105	3	6860007924a6a	Braian	status	status	pending	delivered	Estado cambiado: pending ÔåÆ delivered	127.0.0.1 (localhost)	\N	2025-11-22 22:59:45	T
106	2	6860007924a6a	Braian	status	status	pending	delivered	Estado cambiado: pending ÔåÆ delivered	127.0.0.1 (localhost)	\N	2025-11-22 22:59:45	T
107	1	6860007924a6a	Braian	status	status	pending	delivered	Estado cambiado: pending ÔåÆ delivered	127.0.0.1 (localhost)	\N	2025-11-22 22:59:45	T
108	3	6860007924a6a	Braian	payment_status	payment_status	pending	paid	Estado de pago actualizado	::1	\N	2025-11-22 23:00:38	T
109	3	6860007924a6a	Braian	payment_status	payment_status	pending	paid	Cambio de estado de pago	::1	\N	2025-11-22 23:00:38	T
110	2	6860007924a6a	Braian	payment_status	payment_status	pending	paid	Estado de pago actualizado	::1	\N	2025-11-22 23:00:50	T
111	2	6860007924a6a	Braian	payment_status	payment_status	pending	paid	Cambio de estado de pago	::1	\N	2025-11-22 23:00:50	T
112	1	6860007924a6a	Braian	payment_status	payment_status	pending	paid	Estado de pago actualizado	::1	\N	2025-11-22 23:01:03	T
113	1	6860007924a6a	Braian	payment_status	payment_status	pending	paid	Cambio de estado de pago	::1	\N	2025-11-22 23:01:03	T
114	9	6861e06ddcf49	Cliente	created	order_created	\N	ORD2025112208851F	Orden #ORD2025112208851F creada con total de $583,000	\N	\N	2025-11-22 23:07:44	T
115	9	6860007924a6a	Braian	payment_status	payment_status	pending	paid	Estado de pago actualizado	::1	\N	2025-11-22 23:09:00	T
116	9	6860007924a6a	Braian	payment_status	payment_status	pending	paid	Cambio de estado de pago	::1	\N	2025-11-22 23:09:00	T
117	9	6860007924a6a	Braian	payment_status	payment_status	paid	pending	Estado de pago actualizado	::1	\N	2025-11-22 23:13:52	T
118	9	6860007924a6a	Braian	payment_status	payment_status	paid	pending	Cambio de estado de pago	::1	\N	2025-11-22 23:13:52	T
119	9	6860007924a6a	Braian	payment_status	payment_status	pending	paid	Estado de pago actualizado	::1	\N	2025-11-22 23:15:18	T
120	9	6860007924a6a	Braian	payment_status	payment_status	pending	paid	Cambio de estado de pago	::1	\N	2025-11-22 23:15:18	T
121	9	6860007924a6a	Braian	status	status	pending	shipped	Estado cambiado: pending ÔåÆ shipped	127.0.0.1 (localhost)	\N	2025-11-22 23:21:01	T
122	10	6922930b94130	Cliente	created	order_created	\N	ORD20251122DBE295	Orden #ORD20251122DBE295 creada con total de $358,000	\N	\N	2025-11-22 23:54:53	T
123	11	6861e06ddcf49	Cliente	created	order_created	\N	ORD2025112335EAC5	Orden #ORD2025112335EAC5 creada con total de $308,000	\N	\N	2025-11-23 00:04:19	T
124	11	6860007924a6a	Braian	payment_status	payment_status	pending	paid	Estado de pago actualizado	::1	\N	2025-11-23 00:06:18	T
125	11	6860007924a6a	Braian	payment_status	payment_status	pending	paid	Cambio de estado de pago	::1	\N	2025-11-23 00:06:18	T
126	11	6860007924a6a	Braian	status	status	pending	shipped	Estado cambiado: pending ÔåÆ shipped	127.0.0.1 (localhost)	\N	2025-11-23 00:06:23	T
127	12	6861e06ddcf49	Cliente	created	order_created	\N	ORD20251125EDC719	Orden #ORD20251125EDC719 creada con total de $88,000	\N	\N	2025-11-25 22:09:34	T
128	13	6861e06ddcf49	Cliente	created	order_created	\N	ORD2025112501BD10	Orden #ORD2025112501BD10 creada con total de $53,000	\N	\N	2025-11-25 22:43:44	T
129	14	6861e06ddcf49	Cliente	created	order_created	\N	ORD20251125A8B68D	Orden #ORD20251125A8B68D creada con total de $53,000	\N	\N	2025-11-25 23:05:14	T
130	15	6861e06ddcf49	Cliente	created	order_created	\N	ORD20251125FB1BE6	Orden #ORD20251125FB1BE6 creada con total de $98,000	\N	\N	2025-11-25 23:09:51	T
131	16	6861e06ddcf49	Cliente	created	order_created	\N	ORD20251125AA48F4	Orden #ORD20251125AA48F4 creada con total de $88,000	\N	\N	2025-11-25 23:11:22	T
132	17	6861e06ddcf49	Cliente	created	order_created	\N	ORD202511255DCC59	Orden #ORD202511255DCC59 creada con total de $88,000	\N	\N	2025-11-25 23:20:37	T
133	17	6860007924a6a	Braian	payment_status	payment_status	pending	paid	Estado de pago actualizado	::1	\N	2025-11-25 23:22:34	T
134	17	6860007924a6a	Braian	payment_status	payment_status	pending	paid	Cambio de estado de pago	::1	\N	2025-11-25 23:22:34	T
135	17	6860007924a6a	Braian	status	status	pending	processing	Estado cambiado: pending ÔåÆ processing	127.0.0.1 (localhost)	\N	2025-11-25 23:22:42	T
136	17	6860007924a6a	Braian	status	status	processing	shipped	Estado cambiado: processing ÔåÆ shipped	127.0.0.1 (localhost)	\N	2025-11-25 23:23:20	T
137	17	6860007924a6a	Braian	status	status	shipped	delivered	Estado cambiado: shipped ÔåÆ delivered	127.0.0.1 (localhost)	\N	2025-11-25 23:23:44	T
138	16	6860007924a6a	Braian	payment_status	payment_status	pending	paid	Estado de pago actualizado	::1	\N	2025-11-25 23:26:54	T
139	16	6860007924a6a	Braian	payment_status	payment_status	pending	paid	Cambio de estado de pago	::1	\N	2025-11-25 23:26:54	T
140	16	6860007924a6a	Braian	status	status	pending	delivered	Estado cambiado: pending ÔåÆ delivered	127.0.0.1 (localhost)	\N	2025-11-25 23:27:00	T
141	15	\N	Sistema	status	status	pending	cancelled	Estado cambiado: pending ÔåÆ cancelled	\N	\N	2025-11-26 00:11:24	T
142	15	\N	Sistema	payment_status	payment_status	pending	refunded	Estado de pago actualizado	\N	\N	2025-11-26 00:11:24	T
143	13	\N	Sistema	status	status	pending	cancelled	Estado cambiado: pending ÔåÆ cancelled	\N	\N	2025-11-26 00:17:11	T
144	13	\N	Sistema	payment_status	payment_status	pending	refunded	Estado de pago actualizado	\N	\N	2025-11-26 00:17:11	T
145	12	6860007924a6a	Braian	status	status	pending	cancelled	Estado cambiado: pending ÔåÆ cancelled	127.0.0.1 (localhost)	\N	2025-11-26 00:23:38	T
146	12	6860007924a6a	Braian	payment_status	payment_status	pending	refunded	Estado de pago actualizado	127.0.0.1 (localhost)	\N	2025-11-26 00:23:38	T
147	15	6860007924a6a	Braian	status	status	cancelled	refunded	Estado cambiado: cancelled ÔåÆ refunded	127.0.0.1 (localhost)	\N	2025-11-26 00:24:04	T
148	15	6860007924	6860007924	refunded	payment_status	98000	refunded	Reembolso de 98.000 registrado en sistema. Ref: 434342	::1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36	2025-11-26 00:24:04	T
149	18	6927787315395	Cliente	created	order_created	\N	ORD202511263173E3	Orden #ORD202511263173E3 creada con total de $548,000	\N	\N	2025-11-26 17:14:59	T
150	19	6861e06ddcf49	Cliente	created	order_created	\N	ORD202511263E6FF6	Orden #ORD202511263E6FF6 creada con total de $88,000	\N	\N	2025-11-26 17:19:31	T
151	19	6860007924a6a	Braian	payment_status	payment_status	pending	paid	Estado de pago actualizado	::1	\N	2025-11-26 17:20:42	T
152	19	6860007924a6a	Braian	payment_status	payment_status	pending	paid	Cambio de estado de pago	::1	\N	2025-11-26 17:20:42	T
153	19	6860007924a6a	Braian	status	status	pending	processing	Estado cambiado: pending ÔåÆ processing	127.0.0.1 (localhost)	\N	2025-11-26 17:20:51	T
154	19	6860007924a6a	Braian	status	status	processing	delivered	Estado cambiado: processing ÔåÆ delivered	127.0.0.1 (localhost)	\N	2025-11-26 17:21:14	T
\.


--
-- TOC entry 5608 (class 0 OID 173497)
-- Dependencies: 284
-- Data for Name: order_views; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.order_views (id, order_id, user_id, viewed_at, trial551) FROM stdin;
1	1	6860007924a6a	2025-11-16 08:27:13	T
2	2	6860007924a6a	2025-11-16 08:27:13	T
4	3	6860007924a6a	2025-11-16 10:30:11	T
5	4	6860007924a6a	2025-11-16 10:30:11	T
6	5	6860007924a6a	2025-11-16 10:30:11	T
7	6	6860007924a6a	2025-11-16 10:30:11	T
8	7	6860007924a6a	2025-11-16 10:30:11	T
11	8	6860007924a6a	2025-11-16 11:58:35	T
12	9	6860007924a6a	2025-11-22 23:08:18	T
13	10	6860007924a6a	2025-11-23 00:05:54	T
14	11	6860007924a6a	2025-11-23 00:05:54	T
15	12	6860007924a6a	2025-11-25 23:21:58	T
16	13	6860007924a6a	2025-11-25 23:21:58	T
17	14	6860007924a6a	2025-11-25 23:21:58	T
18	15	6860007924a6a	2025-11-25 23:21:58	T
19	16	6860007924a6a	2025-11-25 23:21:58	T
20	17	6860007924a6a	2025-11-25 23:21:58	T
22	18	6860007924a6a	2025-11-26 17:20:15	T
23	19	6860007924a6a	2025-11-26 17:20:15	T
\.


--
-- TOC entry 5612 (class 0 OID 173542)
-- Dependencies: 288
-- Data for Name: orders; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.orders (id, order_number, invoice_number, user_id, status, subtotal, shipping_cost, discount_amount, total, payment_method, payment_status, shipping_address, shipping_city, shipping_method_id, shipping_address_id, billing_address, billing_address_id, notes, created_at, updated_at, invoice_resolution, invoice_date, trial554) FROM stdin;
1	TEST37D9B8	\N	6861e06ddcf49	delivered	35000.00	10000.00	0.00	45000.00	transfer	paid	Dirección de prueba	Medellín	\N	\N	\N	\N	\N	2025-11-15 22:10:11	2025-11-22 23:01:03	\N	\N	T
2	ORD20251115C9DBC9	\N	6861e06ddcf49	delivered	35000.00	8000.00	0.00	36000.00	transfer	paid	Calle 63A, Comuna 8 - Villa Hermosa	Medellín	\N	8	\N	\N	\N	2025-11-15 22:17:00	2025-11-22 23:00:50	\N	\N	T
3	ORD2025111677ADAB	\N	6861e06ddcf49	delivered	35000.00	0.00	0.00	35000.00	transfer	paid	Calle 63A, Comuna 8 - Villa Hermosa	Medellín	4	8	\N	\N	\N	2025-11-16 09:09:59	2025-11-22 23:00:38	\N	\N	T
4	ORD20251116134F34	\N	6861e06ddcf49	delivered	35000.00	0.00	0.00	35000.00	transfer	paid	Calle 63A, Comuna 8 - Villa Hermosa	Medellín	4	8	\N	\N	\N	2025-11-16 09:12:17	2025-11-22 22:58:52	\N	\N	T
5	ORD20251116448A7E	\N	6861e06ddcf49	delivered	35000.00	0.00	0.00	35000.00	transfer	paid	Calle 63A, Comuna 8 - Villa Hermosa	Medellín	4	8	\N	\N	\N	2025-11-16 09:16:36	2025-11-22 22:59:25	\N	\N	T
7	ORD20251116EAA8AB	\N	6861e06ddcf49	delivered	35000.00	0.00	0.00	35000.00	transfer	paid	Calle 63A, Comuna 8 - Villa Hermosa	Medellín	4	8	\N	\N	\N	2025-11-16 09:54:06	2025-11-22 22:58:30	\N	\N	T
8	ORD202511163AF156	\N	6861e06ddcf49	delivered	35000.00	8000.00	0.00	43000.00	transfer	paid	Calle 63A, Comuna 8 - Villa Hermosa	Medellín	2	8	\N	\N	\N	2025-11-16 11:58:11	2025-11-16 23:01:27	\N	\N	T
9	ORD2025112208851F	\N	6861e06ddcf49	shipped	575000.00	8000.00	0.00	583000.00	transfer	paid	Calle 63A, Comuna 8 - Villa Hermosa	Medellín	2	8	\N	\N	\N	2025-11-22 23:07:44	2025-11-22 23:21:01	\N	\N	T
10	ORD20251122DBE295	\N	6922930b94130	pending	350000.00	8000.00	0.00	358000.00	transfer	pending	Terminal el faro, Comuna 8 - Villa Hermosa	Medellín	2	11	\N	\N	\N	2025-11-22 23:54:53	2025-11-22 23:54:53	\N	\N	T
11	ORD2025112335EAC5	\N	6861e06ddcf49	shipped	300000.00	8000.00	0.00	308000.00	transfer	paid	Terminal el faro, Comuna 8 - Villa Hermosa	Medellín	2	12	\N	\N	\N	2025-11-23 00:04:19	2025-11-23 00:06:23	\N	\N	T
12	ORD20251125EDC719	\N	6861e06ddcf49	cancelled	100000.00	8000.00	0.00	88000.00	transfer	refunded	Terminal el faro, Comuna 8 - Villa Hermosa	Medellín	2	12	\N	\N	\N	2025-11-25 22:09:34	2025-11-26 00:23:38	\N	\N	T
13	ORD2025112501BD10	\N	6861e06ddcf49	cancelled	45000.00	8000.00	0.00	53000.00	transfer	refunded	Terminal el faro, Comuna 8 - Villa Hermosa	Medellín	2	12	\N	\N	\N	2025-11-25 22:43:44	2025-11-26 00:17:11	\N	\N	T
14	ORD20251125A8B68D	\N	6861e06ddcf49	pending	45000.00	8000.00	0.00	53000.00	transfer	pending	Terminal el faro, Comuna 8 - Villa Hermosa	Medellín	2	12	\N	\N	\N	2025-11-25 23:05:14	2025-11-25 23:05:14	\N	\N	T
15	ORD20251125FB1BE6	\N	6861e06ddcf49	refunded	90000.00	8000.00	0.00	98000.00	transfer	refunded	Terminal el faro, Comuna 8 - Villa Hermosa	Medellín	2	12	\N	\N	\N	2025-11-25 23:09:51	2025-11-26 00:24:04	\N	\N	T
16	ORD20251125AA48F4	\N	6861e06ddcf49	delivered	80000.00	8000.00	0.00	88000.00	transfer	paid	Terminal el faro, Comuna 8 - Villa Hermosa	Medellín	2	12	\N	\N	\N	2025-11-25 23:11:22	2025-11-25 23:27:00	\N	\N	T
17	ORD202511255DCC59	\N	6861e06ddcf49	delivered	80000.00	8000.00	0.00	88000.00	transfer	paid	Terminal el faro, Comuna 8 - Villa Hermosa	Medellín	2	12	\N	\N	\N	2025-11-25 23:20:37	2025-11-25 23:23:44	\N	\N	T
18	ORD202511263173E3	\N	6927787315395	pending	675000.00	8000.00	0.00	548000.00	transfer	pending	Carrera 67, Comuna 5 - Castilla	Medellín	2	13	\N	\N	\N	2025-11-26 17:14:59	2025-11-26 17:14:59	\N	\N	T
19	ORD202511263E6FF6	\N	6861e06ddcf49	delivered	80000.00	8000.00	0.00	88000.00	transfer	paid	Terminal el faro, Comuna 8 - Villa Hermosa	Medellín	2	12	\N	\N	\N	2025-11-26 17:19:31	2025-11-26 17:21:14	\N	\N	T
\.


--
-- TOC entry 5614 (class 0 OID 173570)
-- Dependencies: 290
-- Data for Name: password_resets; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.password_resets (id, user_id, token, expires_at, is_used, created_at, trial554) FROM stdin;
1	6861e06ddcf49	$2y$10$zlLyuc34iVZzl7zDOGosV.IHpMaeMPpEEhehvLt9AKSFYXoMnL99G	2025-11-26 01:16:21	0	2025-11-26 01:01:21	T
2	6861e06ddcf49	$2y$10$IXBdGtus6dBdV/VR4J/VZeYUKJ724lVtdTvHr0DwG/0t3tpDXJ63S	2025-11-26 01:18:34	1	2025-11-26 01:03:34	T
\.


--
-- TOC entry 5616 (class 0 OID 173587)
-- Dependencies: 292
-- Data for Name: payment_transactions; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.payment_transactions (id, order_id, user_id, amount, reference_number, payment_proof, status, admin_notes, verified_by, verified_at, created_at, updated_at, trial554) FROM stdin;
22	\N	6861e06ddcf49	43000.00	21212121	uploads/payment_proofs/proof_6861e06ddcf49_1760374683.png	pending	\N	\N	\N	2025-10-13 11:58:03	2025-10-13 11:58:03	T
23	1	6861e06ddcf49	45000.00	TESTREF	\N	pending	\N	\N	\N	2025-11-15 22:10:11	2025-11-15 22:10:11	T
24	2	6861e06ddcf49	36000.00	454543432323	uploads/payment_proofs/proof_6861e06ddcf49_1763263020.png	pending	\N	\N	\N	2025-11-15 22:17:00	2025-11-15 22:17:00	T
25	3	6861e06ddcf49	35000.00	454543432323	uploads/payment_proofs/proof_6861e06ddcf49_1763302199.png	pending	\N	\N	\N	2025-11-16 09:09:59	2025-11-16 09:09:59	T
26	4	6861e06ddcf49	35000.00	454543432323	uploads/payment_proofs/proof_6861e06ddcf49_1763302337.png	pending	\N	\N	\N	2025-11-16 09:12:17	2025-11-16 09:12:17	T
27	5	6861e06ddcf49	35000.00	454543432323	uploads/payment_proofs/proof_6861e06ddcf49_1763302596.png	pending	\N	\N	\N	2025-11-16 09:16:36	2025-11-16 09:16:36	T
29	7	6861e06ddcf49	35000.00	454543432323	uploads/payment_proofs/proof_6861e06ddcf49_1763304846.png	pending	\N	\N	\N	2025-11-16 09:54:06	2025-11-16 09:54:06	T
30	8	6861e06ddcf49	43000.00	21212121	uploads/payment_proofs/proof_6861e06ddcf49_1763312291.png	pending	\N	\N	\N	2025-11-16 11:58:11	2025-11-16 11:58:11	T
31	8	6861e06ddcf49	-43000.00	21212121	\N	verified	Reembolso registrado · Ref: 21212121	6860007924	2025-11-16 22:24:11	2025-11-16 22:24:11	2025-11-16 22:24:11	T
33	9	6861e06ddcf49	583000.00	434342	uploads/payment_proofs/proof_6861e06ddcf49_1763870864.png	pending	\N	\N	\N	2025-11-22 23:07:44	2025-11-22 23:07:44	T
34	10	6922930b94130	358000.00	21212121	uploads/payment_proofs/proof_6922930b94130_1763873693.png	pending	\N	\N	\N	2025-11-22 23:54:53	2025-11-22 23:54:53	T
35	11	6861e06ddcf49	308000.00	21212121	uploads/payment_proofs/proof_6861e06ddcf49_1763874259.png	pending	\N	\N	\N	2025-11-23 00:04:19	2025-11-23 00:04:19	T
36	12	6861e06ddcf49	88000.00	434342	uploads/payment_proofs/proof_6861e06ddcf49_1764126574.png	pending	\N	\N	\N	2025-11-25 22:09:34	2025-11-25 22:09:34	T
37	13	6861e06ddcf49	53000.00	434342	uploads/payment_proofs/proof_6861e06ddcf49_1764128624.png	pending	\N	\N	\N	2025-11-25 22:43:44	2025-11-25 22:43:44	T
38	14	6861e06ddcf49	53000.00	434342	uploads/payment_proofs/proof_6861e06ddcf49_1764129914.png	pending	\N	\N	\N	2025-11-25 23:05:14	2025-11-25 23:05:14	T
39	15	6861e06ddcf49	98000.00	434342	uploads/payment_proofs/proof_6861e06ddcf49_1764130191.png	pending	\N	\N	\N	2025-11-25 23:09:51	2025-11-25 23:09:51	T
40	16	6861e06ddcf49	88000.00	434342	uploads/payment_proofs/proof_6861e06ddcf49_1764130282.png	pending	\N	\N	\N	2025-11-25 23:11:22	2025-11-25 23:11:22	T
41	17	6861e06ddcf49	88000.00	434342	uploads/payment_proofs/proof_6861e06ddcf49_1764130837.png	pending	\N	\N	\N	2025-11-25 23:20:37	2025-11-25 23:20:37	T
42	15	6861e06ddcf49	-98000.00	434342	\N	verified	Reembolso registrado · Ref: 434342	6860007924	2025-11-26 00:24:04	2025-11-26 00:24:04	2025-11-26 00:24:04	T
43	18	6927787315395	548000.00	21212121	uploads/payment_proofs/proof_6927787315395_1764195299.png	pending	\N	\N	\N	2025-11-26 17:14:59	2025-11-26 17:14:59	T
44	19	6861e06ddcf49	88000.00	21212121	uploads/payment_proofs/proof_6861e06ddcf49_1764195571.png	pending	\N	\N	\N	2025-11-26 17:19:31	2025-11-26 17:19:31	T
\.


--
-- TOC entry 5618 (class 0 OID 173611)
-- Dependencies: 294
-- Data for Name: percentage_discounts; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.percentage_discounts (id, discount_code_id, percentage, max_discount_amount, trial554) FROM stdin;
14	14	40.00	0.00	T
15	15	20.00	0.00	T
16	18	20.00	\N	T
17	19	20.00	\N	T
\.


--
-- TOC entry 5620 (class 0 OID 173626)
-- Dependencies: 296
-- Data for Name: personal_access_tokens; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.personal_access_tokens (id, tokenable_type, tokenable_id, name, token, abilities, last_used_at, expires_at, created_at, updated_at, trial554) FROM stdin;
\.


--
-- TOC entry 5622 (class 0 OID 173637)
-- Dependencies: 298
-- Data for Name: popular_searches; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.popular_searches (id, search_term, search_count, last_searched, trial554) FROM stdin;
1	ropa deportiva de niños	2	2025-11-16 08:31:39	T
2	ropa	31	2025-11-16 11:57:41	T
3	ropa	1	2025-11-16 20:38:52	T
4	Ropa deportiva	27	2025-11-16 23:58:29	T
5	ropa	1	2025-11-17 10:05:31	T
8	ropa niña	1	2025-09-23 09:09:03	T
33	ropa deportica	1	2025-10-13 11:30:54	T
57	ropa}	1	2025-11-12 09:08:03	T
58	ropa	1	2025-11-17 11:23:18	T
59	ropa	1	2025-11-17 11:24:08	T
60	ropa	1	2025-11-17 15:59:34	T
61	ropa	1	2025-11-17 16:45:58	T
62	ropa	1	2025-11-22 23:05:26	T
63	ropa	1	2025-11-22 23:07:58	T
64	ropa	1	2025-11-22 23:21:36	T
65	ropa	1	2025-11-22 23:24:54	T
66	baño	1	2025-11-22 23:45:15	T
67	pijama	1	2025-11-22 23:45:38	T
68	ropa	1	2025-11-23 00:02:17	T
69	ropa	1	2025-11-25 21:47:58	T
70	ropa	1	2025-11-25 21:48:35	T
71	ropa	1	2025-11-25 22:04:34	T
72	ropa	1	2025-11-25 22:42:59	T
73	ropa	1	2025-11-25 23:03:04	T
74	ropa	1	2025-11-25 23:09:31	T
75	ropa	1	2025-11-25 23:10:29	T
76	ropa	1	2025-11-25 23:10:47	T
77	ropa	1	2025-11-25 23:11:02	T
78	ropa	1	2025-11-25 23:20:16	T
79	ropa	1	2025-11-26 00:34:40	T
80	ropa	1	2025-11-26 17:01:10	T
81	topa	1	2025-11-26 17:02:20	T
82	ropa	1	2025-11-26 17:02:24	T
83	ropa	1	2025-11-26 17:04:04	T
84	ropa	1	2025-11-26 17:04:49	T
85	ropa	1	2025-11-26 17:18:50	T
\.


--
-- TOC entry 5624 (class 0 OID 173654)
-- Dependencies: 300
-- Data for Name: product_collections; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.product_collections (id, product_id, collection_id, display_order, created_at, trial554) FROM stdin;
\.


--
-- TOC entry 5626 (class 0 OID 173663)
-- Dependencies: 302
-- Data for Name: product_color_variants; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.product_color_variants (id, product_id, color_id, is_default, trial554) FROM stdin;
29	72	7	1	T
30	73	14	1	T
31	74	7	1	T
32	75	1	1	T
33	76	2	1	T
35	71	2	1	T
36	71	18	0	T
37	71	1	1	T
38	79	9	0	T
\.


--
-- TOC entry 5628 (class 0 OID 173679)
-- Dependencies: 304
-- Data for Name: product_images; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.product_images (id, product_id, color_variant_id, image_path, alt_text, "order", created_at, is_primary, trial554) FROM stdin;
95	71	\N	uploads/productos/69150408e8b9c_687c4f85cd486_conjunto_niño2.jpg	Ropa deportiva - Imagen principal	0	2025-11-12 17:02:48	1	T
96	72	\N	uploads/productos/69228e57e181e_pijama.webp	Pijama para niñas - Imagen principal	0	2025-11-22 23:32:23	1	T
97	73	\N	uploads/productos/6922913ba6f94_ropa baño.webp	Ropa de baño  - Imagen principal	0	2025-11-22 23:44:43	1	T
98	74	\N	uploads/productos/6922922e35da6_casual.jpg	Casual - Imagen principal	0	2025-11-22 23:48:46	1	T
99	75	\N	uploads/productos/692292b14d663_elegante.webp	conjunto elegante - Imagen principal	0	2025-11-22 23:50:57	1	T
\.


--
-- TOC entry 5630 (class 0 OID 173703)
-- Dependencies: 306
-- Data for Name: product_questions; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.product_questions (id, product_id, user_id, question, created_at, trial554) FROM stdin;
2	71	6861e06ddcf49	es de calidad?	2025-11-17 22:42:43	T
3	75	6922930b94130	es elegante?	2025-11-22 23:52:54	T
4	73	6861e06ddcf49	cuanto puede durar?	2025-11-23 00:03:41	T
\.


--
-- TOC entry 5632 (class 0 OID 173725)
-- Dependencies: 308
-- Data for Name: product_reviews; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.product_reviews (id, product_id, user_id, order_id, rating, title, comment, images, is_verified, is_approved, created_at, updated_at, trial554) FROM stdin;
1	71	6861e06ddcf49	8	5	excelente	me encanta	\N	1	1	2025-11-17 08:52:07	2025-11-17 08:52:07	T
\.


--
-- TOC entry 5634 (class 0 OID 173750)
-- Dependencies: 310
-- Data for Name: product_size_variants; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.product_size_variants (id, color_variant_id, size_id, sku, barcode, price, compare_price, quantity, is_active, trial554) FROM stdin;
38	29	1	I	\N	50000.00	\N	20	1	T
39	29	2	J	\N	50000.00	\N	20	1	T
40	29	4	R	\N	50000.00	\N	40	1	T
41	30	1	O	\N	50000.00	\N	4	1	T
42	30	4	V	\N	50000.00	60000.00	40	1	T
43	31	2	S	\N	50000.00	\N	20	1	T
44	32	3	-	\N	50000.00	\N	30	1	T
45	33	1	\N	\N	1200.00	\N	5	1	T
47	35	1	O	\N	35000.00	\N	17	1	T
48	36	2	P	\N	45000.00	\N	17	1	T
49	37	2	\N	\N	4000.00	\N	10	1	T
50	38	1	\N	\N	5000.00	\N	1	1	T
\.


--
-- TOC entry 5636 (class 0 OID 173767)
-- Dependencies: 312
-- Data for Name: productos_auditoria; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.productos_auditoria (id, nombre, accion, created_at, updated_at, trial554) FROM stdin;
1	Camiseta	Creado	2025-09-23 09:05:59	2025-09-23 09:05:59	T
2	Pantalón	Creado	2025-09-23 09:05:59	2025-09-23 09:05:59	T
\.


--
-- TOC entry 5638 (class 0 OID 173785)
-- Dependencies: 314
-- Data for Name: products; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.products (id, name, slug, description, brand, gender, collection, material, care_instructions, compare_price, price, category_id, collection_id, is_featured, is_active, created_at, updated_at, trial554) FROM stdin;
71	Ropa deportiva	ropa-deportiva		angelow	niño	\N			45000.00	35000.00	4	\N	1	1	2025-11-12 17:02:48	2025-11-25 23:04:38	T
72	Pijama para niñas	pijama-para-ninas		angelow	niña	\N			\N	50000.00	3	3	0	1	2025-11-22 23:32:23	2025-11-22 23:32:23	T
73	Ropa de baño 	ropa-de-bano		angelow	niño	\N			\N	50000.00	8	1	0	1	2025-11-22 23:44:43	2025-11-22 23:44:43	T
74	Casual	casual		angelow	niña	\N			\N	50000.00	6	2	0	1	2025-11-22 23:48:46	2025-11-22 23:48:46	T
75	conjunto elegante	conjunto-elegante		angelow	niño	\N			\N	50000.00	2	4	0	1	2025-11-22 23:50:57	2025-11-22 23:50:57	T
\.


--
-- TOC entry 5640 (class 0 OID 173811)
-- Dependencies: 316
-- Data for Name: question_answers; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.question_answers (id, question_id, user_id, answer, is_seller, created_at, trial554) FROM stdin;
1	2	6860007924a6a	si	1	2025-11-22 23:23:49	T
2	3	6860007924a6a	exacto	1	2025-11-22 23:56:45	T
\.


--
-- TOC entry 5642 (class 0 OID 173834)
-- Dependencies: 318
-- Data for Name: review_votes; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.review_votes (id, review_id, user_id, is_helpful, created_at, trial554) FROM stdin;
3	1	6926914b27f44	1	2025-11-26 00:34:45	T
\.


--
-- TOC entry 5644 (class 0 OID 173850)
-- Dependencies: 320
-- Data for Name: search_history; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.search_history (id, user_id, search_term, created_at, trial554) FROM stdin;
1	6861e06ddcf49	da	2025-07-17 19:11:43	T
2	6861e06ddcf49	ropa deportiva de niños	2025-07-21 17:16:30	T
3	6861e06ddcf49	ropa	2025-11-26 17:18:50	T
4	6861e06ddcf49	ropa	2025-11-26 17:18:50	T
5	6861e06ddcf49	Ropa deportiva	2025-10-20 12:52:46	T
6	6861e06ddcf49	ropa niña	2025-09-23 09:09:03	T
7	68d315cf194ba	ropa	2025-09-23 16:52:27	T
8	68d315cf194ba	ropa deportiva	2025-09-23 16:52:36	T
9	6861e06ddcf49	ropa deportica	2025-10-13 11:30:54	T
10	6860007924a6a	ropa	2025-11-11 15:53:07	T
11	6861e06ddcf49	ropa}	2025-11-12 09:08:03	T
12	691b491806be8	ropa	2025-11-17 11:24:08	T
13	6861e06ddcf49	baño	2025-11-22 23:45:15	T
14	6861e06ddcf49	pijama	2025-11-22 23:45:38	T
15	6926914b27f44	ropa	2025-11-26 00:34:40	T
16	6927787315395	ropa	2025-11-26 17:04:49	T
17	6927787315395	topa	2025-11-26 17:02:20	T
\.


--
-- TOC entry 5645 (class 0 OID 173865)
-- Dependencies: 321
-- Data for Name: sessions; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.sessions (id, user_id, ip_address, user_agent, payload, last_activity, trial554) FROM stdin;
492wkTKhjknXbJuNxMvFyEKy9mPW7HKgBaVUdteG	\N	127.0.0.1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0	YTozOntzOjY6Il90b2tlbiI7czo0MDoiUm00TEl1bm0wY21yZ0NFWVBldG5OUkp3SU02eVBZeXczR0wwOFJYQyI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6Mjc6Imh0dHA6Ly8xMjcuMC4wLjE6ODAwMC9sb2dpbiI7czo1OiJyb3V0ZSI7czoxNToiY3VzdG9tZXJzLmxvZ2luIjt9czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319fQ==	1763184229	T
DBnwg3WEDpilc2ICO9anDS7dGkY7IBHxgllFh9CA	6860007924a6a	127.0.0.1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0	YTo1OntzOjY6Il90b2tlbiI7czo0MDoiRVdQYzFaOFVIU3pNYXUyekdvZFBHMHhYY3dGeGRocVl2RW1TNmVERCI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6MzE6Imh0dHA6Ly8xMjcuMC4wLjE6ODAwMC9taS1jdWVudGEiO3M6NToicm91dGUiO3M6MTk6ImN1c3RvbWVycy5kYXNoYm9hcmQiO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX1zOjM6InVybCI7YTowOnt9czo1MDoibG9naW5fd2ViXzU5YmEzNmFkZGMyYjJmOTQwMTU4MGYwMTRjN2Y1OGVhNGUzMDk4OWQiO3M6MTM6IjY4NjAwMDc5MjRhNmEiO30=	1763240781	T
O6JPHK407aQgE3v5YveDtEGsJmwCYHHmwlEnmn8l	\N	127.0.0.1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0	YTozOntzOjY6Il90b2tlbiI7czo0MDoiS0tRQ1B1dnA0WGFCcDlUaTBlaWZ6cW92eVkyVTNWTURoSHY3TlprWSI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6MjE6Imh0dHA6Ly8xMjcuMC4wLjE6ODAwMCI7czo1OiJyb3V0ZSI7czoxMjoiY2F0YWxvZy5ob21lIjt9czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319fQ==	1764167743	T
uClKxuRaajJdgPKCkT18jnJ7zKdjDOttQf4uT9wV	6861e06ddcf49	127.0.0.1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0	YTo0OntzOjY6Il90b2tlbiI7czo0MDoiU3J4SkRLaGNiYW9VaEhWOWJta1VlbzN6SlMzYkt5b1NvZXZLU0psRiI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6MzE6Imh0dHA6Ly8xMjcuMC4wLjE6ODAwMC9taS1jdWVudGEiO3M6NToicm91dGUiO3M6MTk6ImN1c3RvbWVycy5kYXNoYm9hcmQiO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX1zOjUwOiJsb2dpbl93ZWJfNTliYTM2YWRkYzJiMmY5NDAxNTgwZjAxNGM3ZjU4ZWE0ZTMwOTg5ZCI7czoxMzoiNjg2MWUwNmRkY2Y0OSI7fQ==	1763236709	T
VQYTgoplVbIgD7jvh6kHngtN8IWCOqMWjeNxvo8e	\N	127.0.0.1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0	YTozOntzOjY6Il90b2tlbiI7czo0MDoiYkhXa2Vack45WG8zY3hpWDRwNWJwb1h2VUpZMmllbVBYSVBtVzdpTiI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6Mjc6Imh0dHA6Ly8xMjcuMC4wLjE6ODAwMC9sb2dpbiI7czo1OiJyb3V0ZSI7czoxNToiY3VzdG9tZXJzLmxvZ2luIjt9czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319fQ==	1763228307	T
\.


--
-- TOC entry 5610 (class 0 OID 173513)
-- Dependencies: 286
-- Data for Name: shipping_methods; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.shipping_methods (id, name, description, base_cost, delivery_time, is_active, created_at, updated_at, free_shipping_threshold, available_cities, estimated_days_min, estimated_days_max, city, free_shipping_minimum, icon, trial554) FROM stdin;
1	Envío Rápido	Entrega en 24-48 horas	15000.00	1-2 días hábiles	1	2025-09-28 09:33:13	2025-10-04 17:00:58	\N	\N	1	3	Medellín	0.00	fas fa-shipping-fast	T
2	Envío Estándar	Entrega en 3-5 días hábiles	8000.00	3-5 días hábiles	1	2025-09-28 09:33:13	2025-10-04 17:01:25	\N	\N	1	3	Medellín	0.00	fas fa-truck	T
4	Recogida en Tienda	Recogida en punto físico	0.00	Inmediato	1	2025-09-28 09:33:13	2025-10-04 17:00:01	\N	\N	1	3	Medellín	0.00	fas fa-walking	T
\.


--
-- TOC entry 5647 (class 0 OID 173885)
-- Dependencies: 323
-- Data for Name: shipping_price_rules; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.shipping_price_rules (id, min_price, max_price, shipping_cost, is_active, created_at, updated_at, trial554) FROM stdin;
1	50000.00	100000.00	8000.00	1	2025-07-27 15:17:16	2025-07-27 15:21:17	T
2	0.00	49999.00	20000.00	1	2025-07-27 15:19:03	2025-11-26 00:42:30	T
\.


--
-- TOC entry 5649 (class 0 OID 173903)
-- Dependencies: 325
-- Data for Name: site_settings; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.site_settings (id, setting_key, setting_value, category, updated_by, updated_at, trial554) FROM stdin;
1	store_name	Angelow	brand	6860007924a6a	2025-11-22 16:51:54	T
2	store_tagline	Moda con proposito	brand	6860007924a6a	2025-11-22 16:51:54	T
3	primary_color	#0077b6	brand	6860007924a6a	2025-11-22 16:51:54	T
4	secondary_color	#0f172a	brand	6860007924a6a	2025-11-22 16:51:54	T
5	dashboard_welcome	Bienvenido al panel Angelow	brand	6860007924a6a	2025-11-22 16:51:54	T
6	support_email	soporte3@angelow.com	support	6860007924a6a	2025-11-22 16:52:20	T
7	support_phone	+57 300 000 0000	support	6860007924a6a	2025-11-22 16:51:54	T
8	support_whatsapp	+57 300 000 0000	support	6860007924a6a	2025-11-22 16:51:54	T
9	support_hours	L-V 8:00 a 18:00	support	6860007924a6a	2025-11-22 16:51:54	T
10	support_address	Medellin, Colombia	support	6860007924a6a	2025-11-22 16:51:54	T
11	order_auto_cancel_hours	48	operations	6860007924a6a	2025-11-22 16:51:54	T
12	order_review_window_days	15	operations	6860007924a6a	2025-11-22 16:51:54	T
13	low_stock_threshold	5	operations	6860007924a6a	2025-11-22 16:51:54	T
14	review_auto_approve	1	operations	6860007924a6a	2025-11-22 16:51:54	T
15	currency_code	COP	operations	6860007924a6a	2025-11-22 16:51:54	T
16	analytics_timezone	America/Bogota	operations	6860007924a6a	2025-11-22 16:51:54	T
17	social_instagram	https://instagram.com/angelow	social	6860007924a6a	2025-11-22 16:51:54	T
18	social_facebook	https://facebook.com/angelow	social	6860007924a6a	2025-11-22 16:51:54	T
19	social_tiktok		social	6860007924a6a	2025-11-22 16:51:54	T
20	social_whatsapp	https://wa.me/573000000000	social	6860007924a6a	2025-11-22 16:51:54	T
21	brand_logo	uploads/settings/brand_1763848340_effe1753.png	brand	6860007924a6a	2025-11-22 16:52:20	T
\.


--
-- TOC entry 5651 (class 0 OID 173927)
-- Dependencies: 327
-- Data for Name: sizes; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.sizes (id, name, description, is_active, created_at, trial554) FROM stdin;
1	XS	Extra Small	1	2025-06-21 20:47:14	T
2	S	Small	1	2025-06-21 20:47:14	T
3	M	Medium	1	2025-06-21 20:47:14	T
4	L	Large	1	2025-06-21 20:47:14	T
5	XL	Extra Large	1	2025-06-21 20:47:14	T
6	XXL	Double Extra Large	1	2025-06-21 20:47:14	T
7	3XL	Triple Extra Large	1	2025-06-21 20:47:14	T
\.


--
-- TOC entry 5653 (class 0 OID 173944)
-- Dependencies: 329
-- Data for Name: sliders; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.sliders (id, title, subtitle, image, link, order_position, is_active, created_at, updated_at, trial558) FROM stdin;
1	Bienvenido a Angelow	Moda infantil de calidad	uploads/sliders/slider_1762037218_83c0a163.jpg	/tienda	1	1	2025-11-01 17:20:17	2025-11-01 17:46:58	T
2	Nueva Colección Verano	Descubre las últimas tendencias	uploads/sliders/slider_1762037457_eae6c808.jpg	/tienda?collection=verano	2	1	2025-11-01 17:20:17	2025-11-01 17:50:57	T
3	Ofertas Especiales	Hasta 30% de descuento	uploads/sliders/slider_1762037480_11f1960c.jpg	/tienda?offers=true	3	1	2025-11-01 17:20:17	2025-11-01 17:51:20	T
\.


--
-- TOC entry 5655 (class 0 OID 173969)
-- Dependencies: 331
-- Data for Name: stock_history; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.stock_history (id, variant_id, user_id, previous_qty, new_qty, operation, notes, created_at, trial558) FROM stdin;
\.


--
-- TOC entry 5657 (class 0 OID 173978)
-- Dependencies: 333
-- Data for Name: user_addresses; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.user_addresses (id, user_id, address_type, alias, recipient_name, recipient_phone, address, complement, neighborhood, building_type, building_name, apartment_number, delivery_instructions, is_default, is_active, created_at, updated_at, gps_latitude, gps_longitude, gps_accuracy, gps_timestamp, gps_used, trial558) FROM stdin;
2	6861e06ddcf49	oficina	Trabajo	Braian Oquendo	3013636902	Cra 16D #57 B 163	Bloque 3	Belen	edificio	El miranda	210	llamar antes de llegar	0	1	2025-07-13 17:18:49	2025-11-12 08:27:40	\N	\N	\N	\N	0	T
3	68d315cf194ba	apartamento	casa	leidy	428239348923	cra 16d	bloque2	belen	apartamento	poblado	427823	irwokojr	0	1	2025-09-23 16:50:50	2025-09-23 16:51:39	\N	\N	\N	\N	0	T
4	68d315cf194ba	casa	casa22	leidy	428239348923	cra 16d	bloque2	belen	apartamento	poblado	427823	mkfwkljrlwkr	1	1	2025-09-23 16:51:36	2025-09-23 16:51:39	\N	\N	\N	\N	0	T
8	6861e06ddcf49	casa	casa	leidy	428239348923	Calle 63A	\N	Comuna 8 - Villa Hermosa	casa	\N	\N	\N	0	1	2025-10-13 11:56:49	2025-11-23 00:01:51	6.25617528	-75.55546772	\N	2025-10-13 11:56:49	1	T
10	68d315bcd96ef	casa	Casa Principal	Usuario de Prueba	3001234567	Calle 123 #45-67	\N	Centro	casa	\N	\N	\N	1	1	2025-11-12 09:10:58	2025-11-12 09:10:58	\N	\N	\N	\N	0	T
11	6922930b94130	casa	casa	Braian	3022613326	Terminal el faro	\N	Comuna 8 - Villa Hermosa	casa	\N	\N	llamar	1	1	2025-11-22 23:54:10	2025-11-22 23:54:10	6.25276390	-75.53839710	\N	2025-11-22 23:54:10	1	T
12	6861e06ddcf49	apartamento	casa22	Braian	428239348923	Terminal el faro	\N	Comuna 8 - Villa Hermosa	casa	\N	\N	\N	1	1	2025-11-23 00:01:49	2025-11-23 00:01:51	6.25275723	-75.53862686	\N	2025-11-23 00:01:49	1	T
13	6927787315395	casa	casa	leidy	428239348923	Carrera 67	\N	Comuna 5 - Castilla	casa	\N	\N	\N	1	1	2025-11-26 17:03:46	2025-11-26 17:03:46	6.30327187	-75.56692365	\N	2025-11-26 17:03:46	1	T
\.


--
-- TOC entry 5659 (class 0 OID 174006)
-- Dependencies: 335
-- Data for Name: user_applied_discounts; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.user_applied_discounts (id, user_id, discount_code_id, discount_code, discount_amount, applied_at, expires_at, is_used, used_at, trial558) FROM stdin;
3	6861e06ddcf49	15	E20FA9C5	42000.00	2025-10-04 23:16:59	2025-11-03 23:16:59	1	2025-10-05 00:18:55	T
4	6861e06ddcf49	15	E20FA9C5	42000.00	2025-10-04 23:22:13	2025-11-03 23:22:13	1	2025-10-04 23:23:35	T
5	6861e06ddcf49	15	E20FA9C5	42000.00	2025-10-04 23:23:35	2025-11-03 23:23:35	1	2025-10-04 23:45:21	T
6	6861e06ddcf49	15	E20FA9C5	42000.00	2025-10-04 23:45:21	2025-11-03 23:45:21	1	2025-10-04 23:50:05	T
7	6861e06ddcf49	15	E20FA9C5	42000.00	2025-10-04 23:50:05	2025-11-03 23:50:05	1	2025-10-05 00:18:55	T
8	6861e06ddcf49	15	E20FA9C5	7000.00	2025-10-09 08:58:35	2025-11-08 08:58:35	1	2025-10-09 08:58:45	T
9	6861e06ddcf49	15	E20FA9C5	7000.00	2025-10-09 08:58:45	2025-11-08 08:58:45	1	2025-10-09 08:59:05	T
11	6861e06ddcf49	15	E20FA9C5	7000.00	2025-11-15 22:03:18	2025-12-15 22:03:18	1	2025-11-15 22:04:38	T
12	6861e06ddcf49	15	E20FA9C5	7000.00	2025-11-15 22:04:38	2025-12-15 22:04:38	1	2025-11-15 22:17:00	T
13	6861e06ddcf49	18	F3CE7C69	20000.00	2025-11-25 22:09:13	2025-12-25 22:09:13	1	2025-11-25 22:09:22	T
14	6861e06ddcf49	18	F3CE7C69	20000.00	2025-11-25 22:09:22	2025-12-25 22:09:22	1	2025-11-25 22:09:34	T
15	6927787315395	19	DF95FF17	135000.00	2025-11-26 17:14:10	2025-12-26 17:14:10	1	2025-11-26 17:14:17	T
16	6927787315395	19	DF95FF17	135000.00	2025-11-26 17:14:17	2025-12-26 17:14:17	1	2025-11-26 17:14:59	T
\.


--
-- TOC entry 5542 (class 0 OID 172924)
-- Dependencies: 218
-- Data for Name: users; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.users (id, name, email, phone, password, image, role, is_blocked, created_at, updated_at, last_access, remember_token, token_expiry, trial548) FROM stdin;
39ea4d4c39d54	test6	test6@gmail.com		$2b$12$awazqOHeV5fN4XSF2Ooo1OhRd6Qw8nTcAJ7LPmngUnimk3kOxeiMu	\N	customer	0	2025-11-28 01:13:16	2025-11-28 01:13:16	\N	\N	\N	T
6860007924a6a	Braian	braianoquen@gmail.com	\N	$2y$10$safkUgrODd3iixhDIq/y9eG7RnlUq.I3MAq3OsG4PXOsT7bZoss76	\N	admin	0	2025-06-28 09:47:21	2026-03-18 12:54:59	2026-03-18 12:54:17	\N	\N	T
6861e06ddcf49	Braian	braianoquendurango@gmail.com	3013636902	$2y$10$ZewEfu4XLjwVxfRKNKnLD.5YuKVBGS3hqdv6L/oPZ32YzizQO03Za	6861e06ddcf49_1763441031_8ddfda41.jpg	customer	0	2025-06-29 19:55:10	2025-11-26 17:25:33	2025-11-26 17:25:33	\N	\N	T
6862b7448112f	Juan	braianoquen2@gmail.com	\N	$2y$10$lIkReeDLfMBHL7Mj2Vqrk.0LhoLlVboNNliNulgXzEiIrrexwMtrS	\N	customer	1	2025-06-30 11:11:48	2025-11-07 06:31:34	2025-11-01 18:41:22	\N	\N	T
68d315bcd96ef	andres	andres90@gmail.com	3013636902	$2y$10$vhD59LnPpeGeVIyLVJjdV.hHfKop6S3r2EyQ.mKbQ.4YzFcZ5YeJq	\N	customer	0	2025-09-23 16:48:45	2025-09-23 16:48:49	2025-09-23 16:48:49	\N	\N	T
68d315cf194ba	Braian Andres Oquendo Durango	tracongames2@gmail.com	\N	\N	\N	customer	0	2025-09-23 16:49:03	2025-09-23 16:55:02	2025-09-23 16:55:02	\N	\N	T
691b491806be8	andres	andres80@gmail.com	3013636902	$2y$10$MbPfBZ9MPLYlxrh/1WKciuJq9piCVcCa.7xSB9ttV5FlbvSU/RVFS	\N	customer	0	2025-11-17 11:11:04	2025-11-17 11:44:17	2025-11-17 11:44:17	\N	\N	T
6922930b94130	Andres	andres90@gmai.com	3013636902	$2y$10$wsQwH1tOCWS1Qv5f7.Ie/.62eaBArPOOuFX25QZjMPzveYzz0tffq	\N	customer	0	2025-11-22 23:52:27	2025-11-22 23:55:37	2025-11-22 23:55:37	\N	\N	T
6926914b27f44	Rodrigo	tracongamescorreos@gmail.com	3013636902	$2y$10$7eDB639TGYQwZAX27.LD7uzUwhy4iE32LuDkbflAhqqPpT/.7BoUe	\N	customer	0	2025-11-26 00:34:03	2025-11-26 00:35:00	2025-11-26 00:35:00	\N	\N	T
6927787315395	prueba	prueba90@gmail.com	3013636902	$2y$10$PXBZN7dpgqR3uNr2qAeX9uHfejLOhZPu2KtanhwlGqXcQljCUNDKS	\N	customer	0	2025-11-26 17:00:19	2025-11-26 17:16:01	2025-11-26 17:16:01	\N	\N	T
901178e935724	test4	test3@gmail.com	301262621	$2b$12$JE3cjRgsUdKUzEafHPTmhuTKopZgP2cpokXtMvwGK0RyQAvl3k/fW	\N	customer	0	2025-11-28 00:09:07	2025-11-28 00:14:30	\N	\N	\N	T
b540a67d4b494	Braian777	braian777@gmail.com	3013636902	$2b$12$6v/Vu/Llnq3x.NIp2j769e0bhHWvUHfZFI1R6yv/P9UZtdBOzGIou	\N	customer	0	2025-11-27 22:46:56	2025-11-27 22:48:21	\N	\N	\N	T
c76ec6ef3df24	Leidy	leidy315@gmail.com	3022613326	$2b$12$7uY4ZilFBCCafSa/ivT/a.FsOldplqoUQOBNXM17HrJs4QPKrxZFq	\N	customer	0	2025-11-27 09:23:52	2025-11-27 09:23:52	\N	\N	\N	T
dbf462a655c64	juan	juan@gmail.com	3013636902	$2b$12$oydHQpaAvhUUpwvjluzPYO.tFLi4QEFHvDnMsghW7EB7TZm4Wv43S	\N	customer	0	2025-11-23 12:58:24	2025-11-23 12:58:24	\N	\N	\N	T
\.


--
-- TOC entry 5661 (class 0 OID 174023)
-- Dependencies: 337
-- Data for Name: variant_images; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.variant_images (id, color_variant_id, product_id, image_id, image_path, alt_text, "order", is_primary, created_at, trial558) FROM stdin;
85	29	72	\N	uploads/productos/69228e57e2381_pijama.webp	Pijama para niñas - Imagen 1	0	1	2025-11-22 23:32:23	T
86	29	72	\N	uploads/productos/69228e57e28bc_pijama2.webp	Pijama para niñas - Imagen 2	1	0	2025-11-22 23:32:23	T
87	30	73	\N	uploads/productos/6922913ba74ff_ropa baño.webp	Ropa de baño  - Imagen 1	0	1	2025-11-22 23:44:43	T
88	31	74	\N	uploads/productos/6922922e36188_casual.jpg	Casual - Imagen 1	0	1	2025-11-22 23:48:46	T
89	32	75	\N	uploads/productos/692292b14de70_elegante.webp	conjunto elegante - Imagen 1	0	1	2025-11-22 23:50:57	T
90	35	71	\N	uploads/productos/69150408e9140_687c4f85cd486_conjunto_niño2.jpg	Ropa deportiva - Imagen 1	0	1	2025-11-25 23:04:38	T
91	35	71	\N	uploads/productos/69150408e974b_687c4f85cdaf5_deportivo.jpg	Ropa deportiva - Imagen 2	1	0	2025-11-25 23:04:38	T
92	36	71	\N	uploads/productos/69150408e9d55_687c4f8612d1c_coleccion primavera.jpg	Ropa deportiva - Imagen 1	0	1	2025-11-25 23:04:38	T
93	36	71	\N	uploads/productos/69150408e9fac_687c4f8623faa_conjunto_niño.jpg	Ropa deportiva - Imagen 2	1	0	2025-11-25 23:04:38	T
94	36	71	\N	uploads/productos/69150408ea213_687c4f8624a68_deportivo2.jpg	Ropa deportiva - Imagen 3	2	0	2025-11-25 23:04:38	T
95	36	71	\N	uploads/productos/69150408ea507_687c4f8623137_conjunto_niña2.jpg	Ropa deportiva - Imagen 4	3	0	2025-11-25 23:04:38	T
\.


--
-- TOC entry 5663 (class 0 OID 174047)
-- Dependencies: 339
-- Data for Name: wishlist; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.wishlist (id, user_id, product_id, created_at, trial558) FROM stdin;
1	6860007924a6a	71	2025-11-15 16:55:46	T
\.


--
-- TOC entry 5727 (class 0 OID 0)
-- Dependencies: 219
-- Name: admin_notification_dismissals_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.admin_notification_dismissals_id_seq', 1, true);


--
-- TOC entry 5728 (class 0 OID 0)
-- Dependencies: 221
-- Name: announcements_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.announcements_id_seq', 2, true);


--
-- TOC entry 5729 (class 0 OID 0)
-- Dependencies: 224
-- Name: audit_orders_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.audit_orders_id_seq', 177, true);


--
-- TOC entry 5730 (class 0 OID 0)
-- Dependencies: 226
-- Name: audit_users_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.audit_users_id_seq', 18, true);


--
-- TOC entry 5731 (class 0 OID 0)
-- Dependencies: 228
-- Name: bank_account_config_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.bank_account_config_id_seq', 2, true);


--
-- TOC entry 5732 (class 0 OID 0)
-- Dependencies: 230
-- Name: bulk_discount_rules_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.bulk_discount_rules_id_seq', 2, true);


--
-- TOC entry 5733 (class 0 OID 0)
-- Dependencies: 234
-- Name: cart_items_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.cart_items_id_seq', 42, true);


--
-- TOC entry 5734 (class 0 OID 0)
-- Dependencies: 236
-- Name: carts_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.carts_id_seq', 31, true);


--
-- TOC entry 5735 (class 0 OID 0)
-- Dependencies: 238
-- Name: categories_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.categories_id_seq', 8, true);


--
-- TOC entry 5736 (class 0 OID 0)
-- Dependencies: 240
-- Name: collections_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.collections_id_seq', 5, true);


--
-- TOC entry 5737 (class 0 OID 0)
-- Dependencies: 242
-- Name: colombian_banks_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.colombian_banks_id_seq', 27, true);


--
-- TOC entry 5738 (class 0 OID 0)
-- Dependencies: 244
-- Name: colors_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.colors_id_seq', 20, true);


--
-- TOC entry 5739 (class 0 OID 0)
-- Dependencies: 246
-- Name: discount_code_products_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.discount_code_products_id_seq', 1, false);


--
-- TOC entry 5740 (class 0 OID 0)
-- Dependencies: 248
-- Name: discount_code_usage_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.discount_code_usage_id_seq', 1, false);


--
-- TOC entry 5741 (class 0 OID 0)
-- Dependencies: 250
-- Name: discount_codes_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.discount_codes_id_seq', 19, true);


--
-- TOC entry 5742 (class 0 OID 0)
-- Dependencies: 252
-- Name: discount_types_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.discount_types_id_seq', 3, true);


--
-- TOC entry 5743 (class 0 OID 0)
-- Dependencies: 254
-- Name: eliminaciones_auditoria_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.eliminaciones_auditoria_id_seq', 1, false);


--
-- TOC entry 5744 (class 0 OID 0)
-- Dependencies: 256
-- Name: failed_jobs_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.failed_jobs_id_seq', 1, false);


--
-- TOC entry 5745 (class 0 OID 0)
-- Dependencies: 258
-- Name: fixed_amount_discounts_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.fixed_amount_discounts_id_seq', 1, false);


--
-- TOC entry 5746 (class 0 OID 0)
-- Dependencies: 260
-- Name: free_shipping_discounts_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.free_shipping_discounts_id_seq', 1, false);


--
-- TOC entry 5747 (class 0 OID 0)
-- Dependencies: 262
-- Name: google_auth_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.google_auth_id_seq', 7, true);


--
-- TOC entry 5748 (class 0 OID 0)
-- Dependencies: 265
-- Name: jobs_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.jobs_id_seq', 1, false);


--
-- TOC entry 5749 (class 0 OID 0)
-- Dependencies: 267
-- Name: login_attempts_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.login_attempts_id_seq', 31, true);


--
-- TOC entry 5750 (class 0 OID 0)
-- Dependencies: 269
-- Name: migrations_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.migrations_id_seq', 4, true);


--
-- TOC entry 5751 (class 0 OID 0)
-- Dependencies: 271
-- Name: notification_preferences_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.notification_preferences_id_seq', 3, true);


--
-- TOC entry 5752 (class 0 OID 0)
-- Dependencies: 273
-- Name: notification_queue_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.notification_queue_id_seq', 1, false);


--
-- TOC entry 5753 (class 0 OID 0)
-- Dependencies: 275
-- Name: notification_types_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.notification_types_id_seq', 5, true);


--
-- TOC entry 5754 (class 0 OID 0)
-- Dependencies: 277
-- Name: notifications_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.notifications_id_seq', 70, true);


--
-- TOC entry 5755 (class 0 OID 0)
-- Dependencies: 279
-- Name: order_items_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.order_items_id_seq', 24, true);


--
-- TOC entry 5756 (class 0 OID 0)
-- Dependencies: 281
-- Name: order_status_history_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.order_status_history_id_seq', 154, true);


--
-- TOC entry 5757 (class 0 OID 0)
-- Dependencies: 283
-- Name: order_views_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.order_views_id_seq', 23, true);


--
-- TOC entry 5758 (class 0 OID 0)
-- Dependencies: 287
-- Name: orders_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.orders_id_seq', 19, true);


--
-- TOC entry 5759 (class 0 OID 0)
-- Dependencies: 289
-- Name: password_resets_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.password_resets_id_seq', 2, true);


--
-- TOC entry 5760 (class 0 OID 0)
-- Dependencies: 291
-- Name: payment_transactions_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.payment_transactions_id_seq', 44, true);


--
-- TOC entry 5761 (class 0 OID 0)
-- Dependencies: 293
-- Name: percentage_discounts_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.percentage_discounts_id_seq', 17, true);


--
-- TOC entry 5762 (class 0 OID 0)
-- Dependencies: 295
-- Name: personal_access_tokens_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.personal_access_tokens_id_seq', 1, false);


--
-- TOC entry 5763 (class 0 OID 0)
-- Dependencies: 297
-- Name: popular_searches_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.popular_searches_id_seq', 85, true);


--
-- TOC entry 5764 (class 0 OID 0)
-- Dependencies: 299
-- Name: product_collections_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.product_collections_id_seq', 1, false);


--
-- TOC entry 5765 (class 0 OID 0)
-- Dependencies: 301
-- Name: product_color_variants_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.product_color_variants_id_seq', 38, true);


--
-- TOC entry 5766 (class 0 OID 0)
-- Dependencies: 303
-- Name: product_images_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.product_images_id_seq', 99, true);


--
-- TOC entry 5767 (class 0 OID 0)
-- Dependencies: 305
-- Name: product_questions_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.product_questions_id_seq', 4, true);


--
-- TOC entry 5768 (class 0 OID 0)
-- Dependencies: 307
-- Name: product_reviews_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.product_reviews_id_seq', 1, true);


--
-- TOC entry 5769 (class 0 OID 0)
-- Dependencies: 309
-- Name: product_size_variants_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.product_size_variants_id_seq', 50, true);


--
-- TOC entry 5770 (class 0 OID 0)
-- Dependencies: 311
-- Name: productos_auditoria_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.productos_auditoria_id_seq', 2, true);


--
-- TOC entry 5771 (class 0 OID 0)
-- Dependencies: 313
-- Name: products_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.products_id_seq', 75, true);


--
-- TOC entry 5772 (class 0 OID 0)
-- Dependencies: 315
-- Name: question_answers_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.question_answers_id_seq', 2, true);


--
-- TOC entry 5773 (class 0 OID 0)
-- Dependencies: 317
-- Name: review_votes_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.review_votes_id_seq', 3, true);


--
-- TOC entry 5774 (class 0 OID 0)
-- Dependencies: 319
-- Name: search_history_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.search_history_id_seq', 17, true);


--
-- TOC entry 5775 (class 0 OID 0)
-- Dependencies: 285
-- Name: shipping_methods_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.shipping_methods_id_seq', 4, true);


--
-- TOC entry 5776 (class 0 OID 0)
-- Dependencies: 322
-- Name: shipping_price_rules_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.shipping_price_rules_id_seq', 2, true);


--
-- TOC entry 5777 (class 0 OID 0)
-- Dependencies: 324
-- Name: site_settings_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.site_settings_id_seq', 21, true);


--
-- TOC entry 5778 (class 0 OID 0)
-- Dependencies: 326
-- Name: sizes_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.sizes_id_seq', 7, true);


--
-- TOC entry 5779 (class 0 OID 0)
-- Dependencies: 328
-- Name: sliders_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.sliders_id_seq', 3, true);


--
-- TOC entry 5780 (class 0 OID 0)
-- Dependencies: 330
-- Name: stock_history_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.stock_history_id_seq', 1, false);


--
-- TOC entry 5781 (class 0 OID 0)
-- Dependencies: 332
-- Name: user_addresses_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.user_addresses_id_seq', 13, true);


--
-- TOC entry 5782 (class 0 OID 0)
-- Dependencies: 334
-- Name: user_applied_discounts_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.user_applied_discounts_id_seq', 16, true);


--
-- TOC entry 5783 (class 0 OID 0)
-- Dependencies: 336
-- Name: variant_images_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.variant_images_id_seq', 95, true);


--
-- TOC entry 5784 (class 0 OID 0)
-- Dependencies: 338
-- Name: wishlist_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.wishlist_id_seq', 1, true);


--
-- TOC entry 5257 (class 2606 OID 172922)
-- Name: access_tokens pk_access_tokens; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.access_tokens
    ADD CONSTRAINT pk_access_tokens PRIMARY KEY (id);


--
-- TOC entry 5262 (class 2606 OID 172960)
-- Name: admin_notification_dismissals pk_admin_notification_dismissals; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.admin_notification_dismissals
    ADD CONSTRAINT pk_admin_notification_dismissals PRIMARY KEY (id);


--
-- TOC entry 5265 (class 2606 OID 172989)
-- Name: announcements pk_announcements; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.announcements
    ADD CONSTRAINT pk_announcements PRIMARY KEY (id);


--
-- TOC entry 5267 (class 2606 OID 173021)
-- Name: audit_orders pk_audit_orders; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.audit_orders
    ADD CONSTRAINT pk_audit_orders PRIMARY KEY (id);


--
-- TOC entry 5269 (class 2606 OID 173043)
-- Name: audit_users pk_audit_users; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.audit_users
    ADD CONSTRAINT pk_audit_users PRIMARY KEY (id);


--
-- TOC entry 5271 (class 2606 OID 173062)
-- Name: bank_account_config pk_bank_account_config; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.bank_account_config
    ADD CONSTRAINT pk_bank_account_config PRIMARY KEY (id);


--
-- TOC entry 5273 (class 2606 OID 173080)
-- Name: bulk_discount_rules pk_bulk_discount_rules; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.bulk_discount_rules
    ADD CONSTRAINT pk_bulk_discount_rules PRIMARY KEY (id);


--
-- TOC entry 5275 (class 2606 OID 173087)
-- Name: cache pk_cache; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.cache
    ADD CONSTRAINT pk_cache PRIMARY KEY (key);


--
-- TOC entry 5277 (class 2606 OID 173094)
-- Name: cache_locks pk_cache_locks; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.cache_locks
    ADD CONSTRAINT pk_cache_locks PRIMARY KEY (key);


--
-- TOC entry 5279 (class 2606 OID 173112)
-- Name: cart_items pk_cart_items; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.cart_items
    ADD CONSTRAINT pk_cart_items PRIMARY KEY (id);


--
-- TOC entry 5281 (class 2606 OID 173129)
-- Name: carts pk_carts; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.carts
    ADD CONSTRAINT pk_carts PRIMARY KEY (id);


--
-- TOC entry 5283 (class 2606 OID 173153)
-- Name: categories pk_categories; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.categories
    ADD CONSTRAINT pk_categories PRIMARY KEY (id);


--
-- TOC entry 5285 (class 2606 OID 173177)
-- Name: collections pk_collections; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.collections
    ADD CONSTRAINT pk_collections PRIMARY KEY (id);


--
-- TOC entry 5287 (class 2606 OID 173193)
-- Name: colombian_banks pk_colombian_banks; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.colombian_banks
    ADD CONSTRAINT pk_colombian_banks PRIMARY KEY (id);


--
-- TOC entry 5289 (class 2606 OID 173210)
-- Name: colors pk_colors; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.colors
    ADD CONSTRAINT pk_colors PRIMARY KEY (id);


--
-- TOC entry 5291 (class 2606 OID 173218)
-- Name: discount_code_products pk_discount_code_products; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.discount_code_products
    ADD CONSTRAINT pk_discount_code_products PRIMARY KEY (id);


--
-- TOC entry 5293 (class 2606 OID 173226)
-- Name: discount_code_usage pk_discount_code_usage; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.discount_code_usage
    ADD CONSTRAINT pk_discount_code_usage PRIMARY KEY (id);


--
-- TOC entry 5295 (class 2606 OID 173246)
-- Name: discount_codes pk_discount_codes; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.discount_codes
    ADD CONSTRAINT pk_discount_codes PRIMARY KEY (id);


--
-- TOC entry 5297 (class 2606 OID 173264)
-- Name: discount_types pk_discount_types; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.discount_types
    ADD CONSTRAINT pk_discount_types PRIMARY KEY (id);


--
-- TOC entry 5299 (class 2606 OID 173273)
-- Name: eliminaciones_auditoria pk_eliminaciones_auditoria; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.eliminaciones_auditoria
    ADD CONSTRAINT pk_eliminaciones_auditoria PRIMARY KEY (id);


--
-- TOC entry 5302 (class 2606 OID 173283)
-- Name: failed_jobs pk_failed_jobs; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.failed_jobs
    ADD CONSTRAINT pk_failed_jobs PRIMARY KEY (id);


--
-- TOC entry 5304 (class 2606 OID 173291)
-- Name: fixed_amount_discounts pk_fixed_amount_discounts; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.fixed_amount_discounts
    ADD CONSTRAINT pk_fixed_amount_discounts PRIMARY KEY (id);


--
-- TOC entry 5306 (class 2606 OID 173298)
-- Name: free_shipping_discounts pk_free_shipping_discounts; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.free_shipping_discounts
    ADD CONSTRAINT pk_free_shipping_discounts PRIMARY KEY (id);


--
-- TOC entry 5308 (class 2606 OID 173320)
-- Name: google_auth pk_google_auth; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.google_auth
    ADD CONSTRAINT pk_google_auth PRIMARY KEY (id);


--
-- TOC entry 5310 (class 2606 OID 173327)
-- Name: job_batches pk_job_batches; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.job_batches
    ADD CONSTRAINT pk_job_batches PRIMARY KEY (id);


--
-- TOC entry 5313 (class 2606 OID 173336)
-- Name: jobs pk_jobs; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.jobs
    ADD CONSTRAINT pk_jobs PRIMARY KEY (id);


--
-- TOC entry 5315 (class 2606 OID 173353)
-- Name: login_attempts pk_login_attempts; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.login_attempts
    ADD CONSTRAINT pk_login_attempts PRIMARY KEY (id);


--
-- TOC entry 5317 (class 2606 OID 173368)
-- Name: migrations pk_migrations; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.migrations
    ADD CONSTRAINT pk_migrations PRIMARY KEY (id);


--
-- TOC entry 5319 (class 2606 OID 173388)
-- Name: notification_preferences pk_notification_preferences; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.notification_preferences
    ADD CONSTRAINT pk_notification_preferences PRIMARY KEY (id);


--
-- TOC entry 5321 (class 2606 OID 173400)
-- Name: notification_queue pk_notification_queue; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.notification_queue
    ADD CONSTRAINT pk_notification_queue PRIMARY KEY (id);


--
-- TOC entry 5323 (class 2606 OID 173424)
-- Name: notification_types pk_notification_types; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.notification_types
    ADD CONSTRAINT pk_notification_types PRIMARY KEY (id);


--
-- TOC entry 5325 (class 2606 OID 173450)
-- Name: notifications pk_notifications; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.notifications
    ADD CONSTRAINT pk_notifications PRIMARY KEY (id);


--
-- TOC entry 5327 (class 2606 OID 173472)
-- Name: order_items pk_order_items; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.order_items
    ADD CONSTRAINT pk_order_items PRIMARY KEY (id);


--
-- TOC entry 5329 (class 2606 OID 173495)
-- Name: order_status_history pk_order_status_history; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.order_status_history
    ADD CONSTRAINT pk_order_status_history PRIMARY KEY (id);


--
-- TOC entry 5331 (class 2606 OID 173511)
-- Name: order_views pk_order_views; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.order_views
    ADD CONSTRAINT pk_order_views PRIMARY KEY (id);


--
-- TOC entry 5336 (class 2606 OID 173567)
-- Name: orders pk_orders; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.orders
    ADD CONSTRAINT pk_orders PRIMARY KEY (id);


--
-- TOC entry 5338 (class 2606 OID 173585)
-- Name: password_resets pk_password_resets; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.password_resets
    ADD CONSTRAINT pk_password_resets PRIMARY KEY (id);


--
-- TOC entry 5340 (class 2606 OID 173609)
-- Name: payment_transactions pk_payment_transactions; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.payment_transactions
    ADD CONSTRAINT pk_payment_transactions PRIMARY KEY (id);


--
-- TOC entry 5342 (class 2606 OID 173624)
-- Name: percentage_discounts pk_percentage_discounts; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.percentage_discounts
    ADD CONSTRAINT pk_percentage_discounts PRIMARY KEY (id);


--
-- TOC entry 5346 (class 2606 OID 173633)
-- Name: personal_access_tokens pk_personal_access_tokens; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.personal_access_tokens
    ADD CONSTRAINT pk_personal_access_tokens PRIMARY KEY (id);


--
-- TOC entry 5348 (class 2606 OID 173652)
-- Name: popular_searches pk_popular_searches; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.popular_searches
    ADD CONSTRAINT pk_popular_searches PRIMARY KEY (id);


--
-- TOC entry 5350 (class 2606 OID 173661)
-- Name: product_collections pk_product_collections; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.product_collections
    ADD CONSTRAINT pk_product_collections PRIMARY KEY (id);


--
-- TOC entry 5352 (class 2606 OID 173677)
-- Name: product_color_variants pk_product_color_variants; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.product_color_variants
    ADD CONSTRAINT pk_product_color_variants PRIMARY KEY (id);


--
-- TOC entry 5354 (class 2606 OID 173701)
-- Name: product_images pk_product_images; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.product_images
    ADD CONSTRAINT pk_product_images PRIMARY KEY (id);


--
-- TOC entry 5356 (class 2606 OID 173723)
-- Name: product_questions pk_product_questions; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.product_questions
    ADD CONSTRAINT pk_product_questions PRIMARY KEY (id);


--
-- TOC entry 5358 (class 2606 OID 173748)
-- Name: product_reviews pk_product_reviews; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.product_reviews
    ADD CONSTRAINT pk_product_reviews PRIMARY KEY (id);


--
-- TOC entry 5360 (class 2606 OID 173765)
-- Name: product_size_variants pk_product_size_variants; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.product_size_variants
    ADD CONSTRAINT pk_product_size_variants PRIMARY KEY (id);


--
-- TOC entry 5362 (class 2606 OID 173783)
-- Name: productos_auditoria pk_productos_auditoria; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.productos_auditoria
    ADD CONSTRAINT pk_productos_auditoria PRIMARY KEY (id);


--
-- TOC entry 5364 (class 2606 OID 173809)
-- Name: products pk_products; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.products
    ADD CONSTRAINT pk_products PRIMARY KEY (id);


--
-- TOC entry 5366 (class 2606 OID 173832)
-- Name: question_answers pk_question_answers; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.question_answers
    ADD CONSTRAINT pk_question_answers PRIMARY KEY (id);


--
-- TOC entry 5368 (class 2606 OID 173848)
-- Name: review_votes pk_review_votes; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.review_votes
    ADD CONSTRAINT pk_review_votes PRIMARY KEY (id);


--
-- TOC entry 5370 (class 2606 OID 173864)
-- Name: search_history pk_search_history; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.search_history
    ADD CONSTRAINT pk_search_history PRIMARY KEY (id);


--
-- TOC entry 5372 (class 2606 OID 173881)
-- Name: sessions pk_sessions; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.sessions
    ADD CONSTRAINT pk_sessions PRIMARY KEY (id);


--
-- TOC entry 5333 (class 2606 OID 173540)
-- Name: shipping_methods pk_shipping_methods; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.shipping_methods
    ADD CONSTRAINT pk_shipping_methods PRIMARY KEY (id);


--
-- TOC entry 5376 (class 2606 OID 173901)
-- Name: shipping_price_rules pk_shipping_price_rules; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.shipping_price_rules
    ADD CONSTRAINT pk_shipping_price_rules PRIMARY KEY (id);


--
-- TOC entry 5379 (class 2606 OID 173924)
-- Name: site_settings pk_site_settings; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.site_settings
    ADD CONSTRAINT pk_site_settings PRIMARY KEY (id);


--
-- TOC entry 5381 (class 2606 OID 173942)
-- Name: sizes pk_sizes; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.sizes
    ADD CONSTRAINT pk_sizes PRIMARY KEY (id);


--
-- TOC entry 5383 (class 2606 OID 173967)
-- Name: sliders pk_sliders; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.sliders
    ADD CONSTRAINT pk_sliders PRIMARY KEY (id);


--
-- TOC entry 5385 (class 2606 OID 173976)
-- Name: stock_history pk_stock_history; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.stock_history
    ADD CONSTRAINT pk_stock_history PRIMARY KEY (id);


--
-- TOC entry 5387 (class 2606 OID 174004)
-- Name: user_addresses pk_user_addresses; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.user_addresses
    ADD CONSTRAINT pk_user_addresses PRIMARY KEY (id);


--
-- TOC entry 5389 (class 2606 OID 174021)
-- Name: user_applied_discounts pk_user_applied_discounts; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.user_applied_discounts
    ADD CONSTRAINT pk_user_applied_discounts PRIMARY KEY (id);


--
-- TOC entry 5259 (class 2606 OID 172944)
-- Name: users pk_users; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.users
    ADD CONSTRAINT pk_users PRIMARY KEY (id);


--
-- TOC entry 5391 (class 2606 OID 174045)
-- Name: variant_images pk_variant_images; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.variant_images
    ADD CONSTRAINT pk_variant_images PRIMARY KEY (id);


--
-- TOC entry 5393 (class 2606 OID 174061)
-- Name: wishlist pk_wishlist; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.wishlist
    ADD CONSTRAINT pk_wishlist PRIMARY KEY (id);


--
-- TOC entry 5300 (class 1259 OID 173284)
-- Name: failed_jobs_uuid_unique; Type: INDEX; Schema: public; Owner: postgres
--

CREATE UNIQUE INDEX failed_jobs_uuid_unique ON public.failed_jobs USING btree (uuid);


--
-- TOC entry 5260 (class 1259 OID 172962)
-- Name: idx_notification_key; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_notification_key ON public.admin_notification_dismissals USING btree (notification_key);


--
-- TOC entry 5377 (class 1259 OID 173925)
-- Name: idx_setting_key; Type: INDEX; Schema: public; Owner: postgres
--

CREATE UNIQUE INDEX idx_setting_key ON public.site_settings USING btree (setting_key);


--
-- TOC entry 5334 (class 1259 OID 173568)
-- Name: idx_shipping_method_id; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_shipping_method_id ON public.orders USING btree (shipping_method_id);


--
-- TOC entry 5255 (class 1259 OID 172923)
-- Name: idx_user_id; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_user_id ON public.access_tokens USING btree (user_id);


--
-- TOC entry 5311 (class 1259 OID 173337)
-- Name: jobs_queue_index; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX jobs_queue_index ON public.jobs USING btree (queue);


--
-- TOC entry 5343 (class 1259 OID 173634)
-- Name: personal_access_tokens_token_unique; Type: INDEX; Schema: public; Owner: postgres
--

CREATE UNIQUE INDEX personal_access_tokens_token_unique ON public.personal_access_tokens USING btree (token);


--
-- TOC entry 5344 (class 1259 OID 173635)
-- Name: personal_access_tokens_tokenable_type_tokenable_id_index; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX personal_access_tokens_tokenable_type_tokenable_id_index ON public.personal_access_tokens USING btree (tokenable_type, tokenable_id);


--
-- TOC entry 5373 (class 1259 OID 173883)
-- Name: sessions_last_activity_index; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX sessions_last_activity_index ON public.sessions USING btree (last_activity);


--
-- TOC entry 5374 (class 1259 OID 173882)
-- Name: sessions_user_id_index; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX sessions_user_id_index ON public.sessions USING btree (user_id);


--
-- TOC entry 5263 (class 1259 OID 172961)
-- Name: uniq_admin_notification_key; Type: INDEX; Schema: public; Owner: postgres
--

CREATE UNIQUE INDEX uniq_admin_notification_key ON public.admin_notification_dismissals USING btree (admin_id, notification_key);


--
-- TOC entry 5394 (class 2606 OID 174062)
-- Name: admin_notification_dismissals fk_admin_dismissal_user; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.admin_notification_dismissals
    ADD CONSTRAINT fk_admin_dismissal_user FOREIGN KEY (admin_id) REFERENCES public.users(id) ON DELETE CASCADE;


--
-- TOC entry 5395 (class 2606 OID 174067)
-- Name: orders fk_orders_shipping_method; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.orders
    ADD CONSTRAINT fk_orders_shipping_method FOREIGN KEY (shipping_method_id) REFERENCES public.shipping_methods(id) ON UPDATE CASCADE ON DELETE SET NULL;


-- Completed on 2026-03-24 13:15:13

--
-- PostgreSQL database dump complete
--

\unrestrict yvOWUsNcQThbdHc4Mlirygf0Oy94UboWOdhUn8hlgGYAVklvP5Kp5C8nWS9fczH

