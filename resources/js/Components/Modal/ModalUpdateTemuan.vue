<script setup lang="ts">
import { onMounted, ref, watch } from "vue";

import RequestAPI from "@/utils/RequestAPI";
import Toast from "@/utils/Toast";
import InputUnitUsaha from "../Input/InputUnitUsaha.vue";
import InputBagian from "../Input/InputBagian.vue";
import InputBidang from "../Input/InputBidang.vue";

const props = defineProps<{
    nik: any;
    temuan?: any;
}>();

const emits = defineEmits(["refresh"]);
const diKlik = defineModel("diKlik");
const modal = ref();
const modal2 = ref();
const showModal2 = (data: any, index: any) => {
    itemToDelete.value = data;
    itemToDelete.value.index = index;
    if (itemToDelete.value.id == "") {
        form.value.rekomendasi.splice(itemToDelete.value.index, 1);
        return;
    }
    modal2.value?.showModal();
};

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
    id: "",
    kode_unit: "",
    temuan: "",
    rekomendasi: [{ id: "", rekomendasi: "" }],
    bidang_id: "",
    kode_bagian: "",
});

const tambahRekomendasi = () => {
    form.value.rekomendasi.push({ id: "", rekomendasi: "" });
};

const updateForm = async () => {
    try {
        const res = await RequestAPI.updateTemuan({ data: form.value });
        Toast.showSuccess(res.message);
        form.value = {
            ...form.value,
            id: "",
            kode_unit: "",
            temuan: "",
            rekomendasi: [{ id: "", rekomendasi: "" }],
            bidang_id: "",
            kode_bagian: "",
        };
        emits("refresh");
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
        form.value.kode_bagian = props.temuan?.kode_bagian;
    }
);
watch(
    () => diKlik.value,
    (newValue) => {
        if (newValue === true) {
            form.value = {
                ...form.value,
                id: props.temuan?.id,
                kode_unit: props.temuan?.kode_unit,
                temuan: props.temuan?.temuan,
                rekomendasi: props.temuan?.rekomendasi,
                bidang_id: props.temuan?.bidang_id,
                kode_bagian: props.temuan?.kode_bagian,
            };
            diKlik.value = false;
        }
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

const itemToDelete = ref();
const deleteState = ref("initial");

const deleteRekomendasi = async () => {
    try {
        const res = await RequestAPI.deleteRekomendasi({
            data: itemToDelete.value,
        });
        deleteState.value = "success";
        form.value.rekomendasi.splice(itemToDelete.value.index, 1);
        modal2.value?.close();
        Toast.showSuccess(res.message);
    } catch (e: any) {
        deleteState.value = "error";
    }
};
</script>

<template>
    <dialog ref="modal" id="modalUpdateTemuan" class="modal">
        <div class="w-11/12 max-w-5xl modal-box">
            <h3 class="text-lg font-bold">Perbarui Temuan</h3>
            <p class="py-4">Isi informasi untuk perbarui temuan</p>

            <form @submit.prevent="updateForm">
                <div class="mb-4 form-control">
                    <label class="label">
                        <span class="label-text">Temuan</span>
                    </label>
                    <textarea
                        v-model="form.temuan"
                        placeholder="Masukkan Temuan"
                        class="w-full textarea textarea-bordered"
                        required
                    ></textarea>
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
                            v-model="form.rekomendasi[index].rekomendasi"
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
                            @click="showModal2(form.rekomendasi[index], index)"
                            class="ml-2 btn btn-error"
                        >
                            <v-icon name="hi-trash" color="white" />
                        </button>
                    </div>
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

                <div class="modal-action">
                    <button type="submit" class="text-white btn btn-warning">
                        <v-icon name="hi-refresh" />
                        Perbarui
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
        <dialog ref="modal2" id="showModalDeleteRekomendasi" class="modal">
            <div
                class="flex flex-col items-center justify-center p-8 text-center bg-white rounded-lg shadow-lg modal-box"
            >
                <v-icon
                    name="hi-trash"
                    class="mb-4 text-error"
                    scale="4"
                ></v-icon>
                <h3 class="mb-2 text-2xl font-extrabold text-error">
                    Hapus Rekomendasi
                </h3>
                <p class="mb-4 text-gray-600 text-md">
                    Apakah anda yakin ingin menghapus rekomendasi ini?
                </p>
                <div class="flex justify-center space-x-4 modal-action">
                    <form @submit.prevent="deleteRekomendasi()" class="flex-1">
                        <button
                            class="w-full px-6 py-2 text-white btn btn-error"
                        >
                            Ya, Hapus!
                        </button>
                    </form>
                    <form method="dialog" class="flex-1">
                        <button class="w-full px-6 py-2 btn">
                            Tidak, Simpan Saja
                        </button>
                    </form>
                </div>
            </div>
            <form method="dialog" class="modal-backdrop">
                <button>close</button>
            </form>
        </dialog>
    </dialog>
</template>
