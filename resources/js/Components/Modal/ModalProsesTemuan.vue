<template>
    <dialog ref="modal" class="modal">
        <div
            class="items-center justify-center p-8 text-center bg-white rounded-lg shadow-lg modal-box"
        >
            <v-icon
                name="hi-bookmark"
                class="mb-4 text-accent"
                scale="4"
            ></v-icon>
            <h3 class="mb-2 text-2xl font-extrabold text-accent">
                Proses Temuan
            </h3>
            <p class="mb-4 text-gray-600 text-md">
                Apakah anda yakin ingin proses temuan ini?
            </p>
            <div class="w-full p-4 mb-4 bg-gray-100 rounded-lg shadow-inner">
                <p class="mb-2 text-sm text-left text-gray-700">
                    <span class="font-bold">Temuan: </span>{{ temuan?.temuan }}
                </p>
                <p class="mb-2 text-sm text-left text-gray-700">
                    <span class="font-bold">Rekomendasi: </span>
                    <template
                        v-for="(rekomendasi, index) in temuan?.rekomendasi"
                        ><br
                            v-if="index < rekomendasi.rekomendasi.length - 1"
                        />{{ index + 1 }}.
                        {{ rekomendasi?.rekomendasi }}</template
                    >
                </p>
                <p
                    v-if="temuan?.unit_usaha.kode_unit == '4R00'"
                    class="mb-2 text-sm text-left text-gray-700"
                >
                    <span class="font-bold">Tujuan Unit/Bagian: </span>
                    {{ temuan?.bagian.name }}
                </p>
                <p v-else class="mb-2 text-sm text-left text-gray-700">
                    <span class="font-bold">Tujuan Unit/Bagian: </span>
                    {{ temuan?.unit_usaha.nama_unit }}
                </p>
                <p class="mb-2 text-sm text-left text-gray-700">
                    <span class="font-bold">Status: </span>{{ temuan?.status }}
                </p>
            </div>

            <div class="flex justify-center space-x-4 modal-action">
                <form @submit.prevent="prosesTemuan" class="flex-1">
                    <button class="w-full px-6 py-2 text-white btn btn-accent">
                        Ya, Proses!
                    </button>
                </form>
                <form method="dialog" class="flex-1">
                    <button class="w-full px-6 py-2 btn">
                        Tidak, Nanti Saja
                    </button>
                </form>
            </div>
        </div>
        <form method="dialog" class="modal-backdrop">
            <button>close</button>
        </form>
    </dialog>
</template>
<script lang="ts" setup>
import RequestAPI from "@/utils/RequestAPI";
import Toast from "@/utils/Toast";
import { ref } from "vue";

const props = defineProps<{ temuan?: any; nik: any }>();

const modal = ref();
const showModal = () => {
    modal.value?.showModal();
};
defineExpose({
    showModal,
});
const emits = defineEmits(["refresh"]);
const prosesTemuan = async () => {
    try {
        const form = ref({
            temuan_id: props.temuan.id,
        });
        const res = await RequestAPI.prosesTemuan({ data: form.value });
        Toast.showSuccess(res.message);
        emits("refresh");
        modal.value?.close();
    } catch (e: any) {
        Toast.showError(String(e.message));
        modal.value?.close();
    }
};
</script>
