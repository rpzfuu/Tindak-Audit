<script setup lang="ts">
import { ref, watch } from "vue";

import RequestAPI from "@/utils/RequestAPI";
import Toast from "@/utils/Toast";
import InputUnitUsaha from "../Input/InputUnitUsaha.vue";
import InputBagian from "../Input/InputBagian.vue";
import InputBidang from "../Input/InputBidang.vue";

defineProps<{
    user: any;
}>();

const diSubmit = defineModel();
const modal = ref();

const showModal = () => {
    if (unit.value.state == "initial") {
        getUnit();
    }
    if (bidang.value.state == "initial") {
        getBidang();
    }
    modal.value?.showModal();
};

defineExpose({
    showModal,
});

const form = ref({
    kode_unit: "",
    temuan: "",
    rekomendasi: [""],
    bidang_id: "",
    kode_bagian: "",
});

const tambahRekomendasi = () => {
    form.value.rekomendasi.push("");
};

const hapusRekomendasi = (index: number) => {
    form.value.rekomendasi.splice(index, 1);
};

const submitForm = async () => {
    try {
        const res = await RequestAPI.inputTemuan({ data: form.value });
        Toast.showSuccess(res.message);
        form.value = {
            ...form.value,
            kode_unit: "",
            temuan: "",
            rekomendasi: [""],
            bidang_id: "",
            kode_bagian: "",
        };
        diSubmit.value = true;
        modal.value?.close();
    } catch (e: any) {
        Toast.showError(String(e.message));
    }
};

const bidang = ref<{
    state: "initial" | "loading" | "success" | "error" | "saving";
    message: any;

    data: Array<{
        id: any;
        kode_bidang: any;
        nama_bidang: any;
        created_at: any;
        updated_at: any;
    }>;
}>({
    state: "initial",
    message: "",

    data: [
        {
            id: "",
            kode_bidang: "",
            nama_bidang: "",
            created_at: "",
            updated_at: "",
        },
    ],
});

const unit = ref<{
    state: "initial" | "loading" | "success" | "error" | "saving";
    message: any;
    data: Array<{
        id: any;
        kode_unit: any;
        nama_unit: any;
        kode_grup_unit: any;
        is_saturday_on: any;
        is_head_office: any;
        created_at: any;
        updated_at: any;
        is_active: any;
        bagian: Array<{
            id: any;
            name: any;
            code: any;
            created_at: any;
            updated_at: any;
            kode_unit: any;
            sub_bagian: Array<{
                id: any;
                bagian_code: any;
                name: any;
                code: any;
                created_at: any;
                updated_at: any;
            }>;
        }>;
    }>;
}>({
    state: "initial",
    message: {},
    data: [
        {
            id: "",
            kode_unit: "",
            nama_unit: "",
            kode_grup_unit: "",
            is_saturday_on: "",
            is_head_office: "",
            created_at: "",
            updated_at: "",
            is_active: "",
            bagian: [
                {
                    id: "",
                    name: "",
                    code: "",
                    created_at: "",
                    updated_at: "",
                    kode_unit: "",
                    sub_bagian: [
                        {
                            id: "",
                            bagian_code: "",
                            name: "",
                            code: "",
                            created_at: "",
                            updated_at: "",
                        },
                    ],
                },
            ],
        },
    ],
});

const filteredBagian = ref<
    Array<{
        id: any;
        name: any;
        code: any;
        created_at: any;
        updated_at: any;
        kode_unit: any;
        sub_bagian: Array<{
            id: any;
            bagian_code: any;
            name: any;
            code: any;
            created_at: any;
            updated_at: any;
        }>;
    }>
>([]);

watch(
    () => form.value.kode_unit,
    (newKodeUnit) => {
        const selectedUnit = unit.value.data.find(
            (u) => u.kode_unit === newKodeUnit
        );
        filteredBagian.value = selectedUnit ? selectedUnit.bagian : [];
        form.value.kode_bagian = "";
    }
);

const getUnit = async () => {
    try {
        unit.value.state = "loading";
        const res = (await RequestAPI.getUnit()) as any;
        unit.value.state = "success";
        unit.value = {
            ...unit.value,
            state: "success",
            message: res.message,
            data: res.data,
        };
    } catch (e: any) {
        unit.value.state = "error";
        unit.value.message = String(e.message);
    }
};

const getBidang = async () => {
    try {
        bidang.value.state = "loading";
        const res = (await RequestAPI.getBidang()) as any;
        bidang.value.state = "success";
        bidang.value = {
            ...unit.value,
            state: "success",
            message: res.message,
            data: res.data,
        };
    } catch (e: any) {
        bidang.value.state = "error";
        bidang.value.message = String(e.message);
    }
};
</script>

<template>
    <dialog ref="modal" id="modalTambahTemuan" class="modal">
        <div class="w-11/12 max-w-5xl modal-box">
            <h3 class="text-lg font-bold">Tambah Temuan</h3>
            <p class="py-4">Isi informasi untuk menambah temuan</p>

            <form @submit.prevent="submitForm">
                <div class="mb-4 form-control">
                    <label class="label">
                        <span class="label-text">Temuan</span>
                    </label>
                    <input
                        v-model="form.temuan"
                        placeholder="Masukkan Temuan"
                        class="w-full input input-bordered"
                        required
                    />
                </div>
                <div class="mb-4 form-control">
                    <label class="label">
                        <span class="label-text">Bidang</span>
                    </label>
                    <InputBidang
                        v-model="form.bidang_id"
                        :bidang="bidang.data"
                        :state="bidang.state"
                    />
                </div>
                <div class="mb-4 form-control">
                    <label class="label">
                        <span class="label-text">Unit</span>
                    </label>
                    <InputUnitUsaha
                        v-model="form.kode_unit"
                        :unit="unit.data"
                        :state="unit.state"
                    />
                </div>
                <div class="mb-4 form-control" v-if="form.kode_unit == '4R00'">
                    <label class="label"
                        ><span class="label-text">Bagian</span>
                    </label>
                    <InputBagian
                        v-model="form.kode_bagian"
                        :state="unit.state"
                        :bagian="filteredBagian"
                    />
                </div>
                <div
                    v-for="(rekomendasi, index) in form.rekomendasi"
                    :key="index"
                    class="mb-4 form-control"
                >
                    <label class="label">
                        <span class="label-text"
                            >Rekomendasi {{ index + 1 }}</span
                        >
                    </label>
                    <div class="flex items-center">
                        <input
                            type="text"
                            v-model="form.rekomendasi[index]"
                            placeholder="Masukkan Rekomendasi"
                            class="w-full input input-bordered"
                            required
                        />
                        <button
                            v-if="index === 0"
                            type="button"
                            @click="tambahRekomendasi"
                            class="ml-2 btn btn-success"
                        >
                            <v-icon name="hi-plus" color="white" />
                        </button>
                        <button
                            v-if="form.rekomendasi.length > 1 && index !== 0"
                            type="button"
                            @click="hapusRekomendasi(index)"
                            class="ml-2 btn btn-error"
                        >
                            <v-icon name="hi-trash" color="white" />
                        </button>
                    </div>
                </div>

                <div class="modal-action">
                    <button type="submit" class="text-white btn btn-success">
                        <v-icon name="hi-plus" />
                        Tambah
                    </button>
                    <form method="dialog" class="z-50 modal-backdrop">
                        <button class="btn">
                            <v-icon name="hi-x" /> Close
                        </button>
                    </form>
                </div>
            </form>
        </div>
        <form method="dialog" class="modal-backdrop">
            <button></button>
        </form>
    </dialog>
</template>
